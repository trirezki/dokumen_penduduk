<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDamiuLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('damiu_letters', function (Blueprint $table) {
            $table->id();
            $table->string('prefix');
            $table->string('suffix');
            $table->string('damiu_name');
            $table->string('damiu_address');
            $table->string('business');
            $table->string('verified_file')->nullable();
            $table->unsignedBigInteger('resident_id');
            $table->unsignedBigInteger('official_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->date('end_date');

            $table->foreign('resident_id')->references('id')->on('residents');
            $table->foreign('official_id')->references('id')->on('letter_officials');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('damiu_letters');
    }
}
