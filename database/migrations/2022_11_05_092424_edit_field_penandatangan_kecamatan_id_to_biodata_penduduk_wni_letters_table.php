<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditFieldPenandatanganKecamatanIdToBiodataPendudukWniLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('biodata_penduduk_wni_letters', function (Blueprint $table) {
            $table->unsignedBigInteger('penandatangan_kecamatan_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('biodata_penduduk_wni_letters', function (Blueprint $table) {
            $table->unsignedBigInteger('penandatangan_kecamatan_id')->nullable(false)->change();
        });
    }
}
