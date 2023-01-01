<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditBiodataPendudukWniLettersTable extends Migration
{

    public function up()
    {
        Schema::table('biodata_penduduk_wni_letters', function (Blueprint $table) {
            $table->string('rt_name')->after('dusun')->nullable();
            $table->string('rw_name')->after('rt_name')->nullable();
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
            $table->dropColumn('rt_name');
            $table->dropColumn('rw_name');
        });
    }
}
