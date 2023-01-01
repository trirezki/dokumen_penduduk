<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBiodataPendudukWniLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('biodata_penduduk_wni_letters', function (Blueprint $table) {
            $table->id();
            $table->string('prefix');
            $table->string('suffix');
            $table->string('rt')->nullable();
            $table->string('rw')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('sub_district')->nullable();
            $table->string('village')->nullable();
            $table->string('dusun')->nullable();
            $table->string('verified_file')->nullable();
            $table->unsignedBigInteger('penandatangan_kecamatan_id');
            $table->unsignedBigInteger('penandatangan_desa_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('penandatangan_kecamatan_id')->references('id')->on('letter_officials');
            $table->foreign('penandatangan_desa_id')->references('id')->on('letter_officials');
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
        Schema::dropIfExists('biodata_penduduk_wni_letters');
    }
}
