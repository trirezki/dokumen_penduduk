<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResidentSktmDtksLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resident_sktm_dtks_letters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resident_id');
            $table->unsignedBigInteger('sktm_dtks_letter_id');
            $table->timestamps();

            $table->foreign('resident_id')->references('id')->on('residents');
            $table->foreign('sktm_dtks_letter_id')->references('id')->on('sktm_dtks_letters');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resident_sktm_dtks_letters');
    }
}
