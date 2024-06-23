<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        "pickup",
        "pickup_datetime",
        "destination",
        "passengers",
        "note",
        "whatsapp_number_id",
        "lead_name",
    ];

    public function whatsappNumber(){
        return $this->belongsTo(WhatsappNumber::class, 'whatsapp_number_id');
    }
}
