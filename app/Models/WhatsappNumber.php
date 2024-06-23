<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        "waid",
        "name",
        "dob",
        "assistant",
    ];

    protected $casts = [
        'dob' => 'Y-d-m'
    ];

    public function bookings(){
        return $this->hasMany(Booking::class);
    }
}
