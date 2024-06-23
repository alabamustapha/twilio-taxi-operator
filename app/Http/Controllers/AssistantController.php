<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\WhatsappNumber;
use Illuminate\Http\Request;
use OpenAI;

class AssistantController extends Controller
{
    public function index(){
        return view('welcome')->with('bookings', Booking::paginate(10));
    }

}
