<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFamilySkpwniLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('family_skpwni_letters', function (Blueprint $table) {
            $table->id();
            $table->string('shdk')->nullable();
            $table->unsignedBigInteger('resident_id');
            $table->unsignedBigInteger('skpwni_letter_id');
            $table->timestamps();

            $table->foreign('resident_id')->references('id')->on('residents');
            $table->foreign('skpwni_letter_id')->references('id')->on('skpwni_letters');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('family_skpwni_letters');
    }
}
