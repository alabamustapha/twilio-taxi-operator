<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('lead_name');
            $table->string('pickup');
            $table->string('pickup_datetime');
            $table->string('destination');
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->text('note')->nullable();
            $table->foreignId('whatsapp_number_id');
            $table->foreign('whatsapp_number_id')->references('id')->on('whatsapp_numbers');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};
