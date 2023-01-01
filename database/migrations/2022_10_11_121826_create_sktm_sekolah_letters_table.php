<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSktmSekolahLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sktm_sekolah_letters', function (Blueprint $table) {
            $table->id();
            $table->string('prefix');
            $table->string('suffix');
            $table->string('prefix_desa');
            $table->string('suffix_desa');
            $table->string('dasar_1');
            $table->string('dasar_2');
            $table->string('surat_pengantar')->nullable();
            $table->string('kartu_keluarga')->nullable();
            $table->string('verified_file')->nullable();
            $table->unsignedBigInteger('resident_id');
            $table->string('father');
            $table->string('mother');
            $table->string('used_as');
            $table->string('institution');
            $table->unsignedBigInteger('penandatangan_kecamatan_id');
            $table->unsignedBigInteger('penandatangan_desa_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('resident_id')->references('id')->on('residents');
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
        Schema::dropIfExists('sktm_sekolah_letters');
    }
}
