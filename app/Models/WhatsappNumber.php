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
        "taxi_assistant",
    ];

    protected $casts = [
        'dob' => 'Y-d-m'
    ];
}
