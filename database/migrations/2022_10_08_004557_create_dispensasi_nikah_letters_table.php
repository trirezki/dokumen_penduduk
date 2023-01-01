<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDispensasiNikahLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dispensasi_nikah_letters', function (Blueprint $table) {
            $table->id();
            $table->string('prefix');
            $table->string('suffix');
            $table->string('verified_file')->nullable();
            $table->unsignedBigInteger('pemohon_laki_laki_id');
            $table->unsignedBigInteger('pemohon_perempuan_id');
            $table->unsignedBigInteger('official_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('pemohon_laki_laki_id')->references('id')->on('residents');
            $table->foreign('pemohon_perempuan_id')->references('id')->on('residents');
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
        Schema::dropIfExists('dispensasi_nikah_letters');
    }
}
