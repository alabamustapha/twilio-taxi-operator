<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI;

class AssistantController extends Controller
{
    public function default(){


        $response = <<<RES
            Excellent, thank you for providing all the necessary details. Here's a summary of your booking information:

            {
              "Lead Passenger Name": "Alab",
              "Pickup Address": "Abuja airport",
              "Pickup Time": "15 minutes to Midnight",
              "Destination Address": "Lagos street",
              "Number of Passengers": "3",
              "Special Notes for the Driver": "No special requirements"
            }

            Please check this information and let me know if it's correct.
            RES;



        dd($response);


    }

    public function extractJsonFromText($text){
        
    }
}
