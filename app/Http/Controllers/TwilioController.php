<?php

namespace App\Http\Controllers;

use App\Models\WhatsappNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
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

                    $operator_assistant = $open_ai_client->assistants()->create([
                        'instructions' => "You are an operator for a Taxi Company. Your job is to collect booking information which includes lead passenger name, pickup address, pickup time, destination address, number of passengers, and any special note the client needs to tell the driver. After you gather all responses make sure your last response is a json of the collected details",
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
                            try {

                                $twilio = new TwilioClient(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));

                                $message = $twilio->messages->create("whatsapp:+".$waid, [
                                    'from' => "whatsapp:".env('TWILIO_WHATSAPP_NUMBER'),
                                    'body' => $twilio_body,
                                ]);

                            } catch (\Exception $e) {
                                Log::info("Unable to send message to twilio using WhatsApp");
                            }

                            break;
                        }else if($run->status == "expired" || $run->status == "cancelled" || $run->status == "failed"){
                            try {

                                $twilio = new TwilioClient(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));

                                $message = $twilio->messages->create("whatsapp:+".$waid, [
                                    'from' => "whatsapp:".env('TWILIO_WHATSAPP_NUMBER'),
                                    'body' => "Unable to process last request. try again",
                                ]);

                            } catch (\Exception $e) {
                                Log::info("Unable to send message to twilio using WhatsApp");
                            }
                        }


                };


        }

    }
}
