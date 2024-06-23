<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\TwilioController;
use Illuminate\Support\Facades\Route;
use Twilio\Rest\Client;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [AssistantController::class, 'default'])->name('home');
Route::post('/webhooks/twilio', [TwilioController::class, 'incoming'])->name('incoming');


// Route::get('/test', function () {
//     return "testing";

// });
