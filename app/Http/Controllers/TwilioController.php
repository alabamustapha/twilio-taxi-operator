<?php

namespace App\Http\Controllers;

use App\Mail\BookingRequested;
use App\Models\Booking;
use App\Models\WhatsappNumber;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use OpenAI;
use Twilio\Rest\Client as TwilioClient;

class TwilioController extends Controller
{

    public function incoming(Request $request){

        $open_ai_client = OpenAI::client(env('OPENAI_API_KEY'));

        $name = $request->ProfileName;
        $waid = $request->WaId;
        $message_type = $request->MessageType;
        $message_body = $request->Body;

        if($message_type != "text"){
            try {

                $twilio = new TwilioClient(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));

                $message = $twilio->messages->create("whatsapp:+".$waid, [
                    'from' => "whatsapp:".env('TWILIO_WHATSAPP_NUMBER'),
                    'body' => "Please enter only text input. {$message_type} is not supported",
                ]);

            } catch (\Exception $e) {
                Log::info("Unable to send message to twilio using WhatsApp");
            }
        }else{
                $whatsapp_user = WhatsappNumber::firstOrCreate(['waid' => $waid]);

                // use available assistance or create a new one
                if($whatsapp_user->assistant){
                    $operator_assistant = $open_ai_client->assistants()->retrieve($whatsapp_user->assistant);
                }else{

                    $current_date_time = Carbon::now()->format('Y-m-d H:i');
                    $operator_assistant = $open_ai_client->assistants()->create([
                        'instructions' => <<<PROMPT
                                            You are an operator for a Taxi Company. You are at this desks and today's date and time in the format "Y-m-d H:i" is $current_date_time
                                            Your job is to collect booking information which includes
                                            lead passenger name,
                                            pickup address,
                                            pickup datetime in the format Y-m-d H:i,
                                            destination address,
                                            number of passengers,
                                            and any special note the client needs to tell the driver.
                                            After you gather all responses present the booking details and ask for a confirmation. When user confirms all details make sure your last response is a json of the collected details.
                                            PROMPT,
                        'name' => 'Taxi Operator',
                        'tools' => [],
                        'model' => 'gpt-4',
                    ]);

                    $whatsapp_user->assistant = $operator_assistant->id;
                    $whatsapp_user->save();
                }

                // use available thread or create a new one
                if($whatsapp_user->thread){
                    // add message to thread
                    $thread = $open_ai_client->threads()->messages()->create($whatsapp_user->thread, [
                        'role' => 'user',
                        'content' => $message_body,
                    ]);

                    $run = $open_ai_client->threads()->runs()->create(
                        threadId: $whatsapp_user->thread,
                        parameters: [
                            'assistant_id' => $operator_assistant->id,
                        ],
                    );

                }else{
                    $run = $open_ai_client->threads()->createAndRun(
                        [
                            'assistant_id' => $operator_assistant->id,
                            'thread' => [
                                'messages' =>
                                    [
                                        [
                                            'role' => 'user',
                                            'content' => $message_body,
                                        ],
                                    ],
                            ],
                        ],
                    );

                    $whatsapp_user->thread = $run->threadId;
                    $whatsapp_user->save();
                }


                // run and check thread until completed or cancelled
                while(true){

                        $run = $open_ai_client->threads()->runs()->retrieve(
                            threadId: $whatsapp_user->thread,
                            runId: $run->id,
                        );

                        Log::info(json_encode($run->toArray()));

                        if($run->status == "completed"){
                            $thread_messages = $open_ai_client->threads()->messages()->list($whatsapp_user->thread, [
                                'limit' => 10,
                            ]);

                            $twilio_body = $this->formatOperatorMessage($thread_messages);
                            $booking_info = $this->extractJsonFromText($twilio_body);

                            if($this->bookingDetailsReady($booking_info)){
                                $booking_details_text = $this->formatBookingDetails($booking_info);
                                $this->sendTwilioMessage($waid, $booking_details_text);


                                $this->clearThread($whatsapp_user);

                                $this->saveBooking($whatsapp_user, $booking_info);


                            }else{
                                $this->sendTwilioMessage($waid, $twilio_body);
                            }

                            break;
                        }else if($run->status == "expired" || $run->status == "cancelled" || $run->status == "failed"){

                            $twilio_body =  "Unable to process last request. try again";
                            $this->sendTwilioMessage($waid, $twilio_body);
                            break;
                        }


                };


        }




    }

    private function extractJsonFromText($response_text){
        // Extract the JSON part using regex
        preg_match('/\{.*?\}/s', $response_text, $matches);

        if (isset($matches[0])) {
            $json_string = $matches[0];

            // Decode the JSON string into a PHP array
            $booking_info = json_decode($json_string, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $booking_info = 'error';
            }
        } else {
            $booking_info = 'not_found';
        }

        return $booking_info;
    }

    private function formatOperatorMessage($thread_messages){
        $operator_messages = Arr::where($thread_messages['data'], function($message){
            return $message['role'] == 'assistant';
        });

        $twilio_body = '';
        foreach($operator_messages[0]['content'] as $content){
            if($content['type'] == 'text'){
                $twilio_body .= $content['text']['value'] . "\n";
            }else{
                $twilio_body .= "NTC\n";
            }
        }

        return $twilio_body;
    }

    private function sendTwilioMessage($waid, $twilio_body){

        try {
            $twilio = new TwilioClient(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));

            $message = $twilio->messages->create("whatsapp:+".$waid, [
                'from' => "whatsapp:".env('TWILIO_WHATSAPP_NUMBER'),
                'body' => $twilio_body,
            ]);

        } catch (\Exception $e) {
            Log::info("Unable to send message to twilio using WhatsApp");
            Log::info($e->getMessage());
            try{

                $message = $twilio->messages->create(env('TWILIO_SMS_RECEIVER_NUMBER'), // to
                        array(
                        "from" => env('TWILIO_SMS_NUMBER'),
                        "body" => "We were Unable to send the last reply through WhatsApp, Please retry in WhatsApp"
                        )
                    );
            }catch (\Exception $e) {
                Log::info("Unable to send message through sms");
                Log::info($e->getMessage());
            }
        }
    }

    private function bookingDetailsReady($booking_info){
        Log::info(json_encode($booking_info));
        $ready = false;
        if($booking_info !== 'error' && $booking_info !== 'not_found'){
            Log::info("Array: " . is_array($booking_info));
            Log::info("Count: " . count($booking_info));

            if(is_array($booking_info) && count($booking_info) == 6){
                $ready = true;
            }
        }
        Log::info("Ready: " . $ready);
        return $ready;
    }

    private function formatBookingDetails($booking_info){
        $booking_details_text = 'You booking is now confirmed.';

        foreach($booking_info as $key => $val){
            $booking_details_text .= $key . ": " . $val . "\n";
        }

        return $booking_details_text;
    }

    private function sendNotification($booking){
        Mail::to(env('NOTIFICATION_EMAIL'))->send(new BookingRequested($booking));
    }

    private function clearThread($whatsapp_user){
        $whatsapp_user->thread = null;
        $whatsapp_user->assistant = null;
        $whatsapp_user->save();
    }

    private function saveBooking($whatsapp_user, $booking_info){
        $booking_info = array_values($booking_info);
        $booking = Booking::create([
            "lead_name" => $booking_info[0],
            "pickup" => $booking_info[1],
            "pickup_datetime" => $booking_info[2],
            "destination" => $booking_info[3],
            "passengers" => $booking_info[4],
            "note" => $booking_info[5],
            "whatsapp_number_id" => $whatsapp_user->id
        ]);

        $this->sendNotification($booking);

        sleep(4);

        $this->sendTwilioMessage($whatsapp_user->waid, $this->dispatchNotification($booking));

    }

    private function dispatchNotification($booking){
        $dispatch = 'Dear ' . $booking->lead_name . "\n";
        $dispatch .= "A driver has been dispatched for your booking \n";
        $dispatch .= "Name: " . fake()->name() . "\n";
        $dispatch .= "Phone: " . fake()->phoneNumber() . "\n";
        $dispatch .= "Vehichle Number: " . fake()->jpjNumberPlate() . "\n";
        $dispatch .= "Thank you for using our DispatchBot \n";

        return $dispatch;
    }
}
