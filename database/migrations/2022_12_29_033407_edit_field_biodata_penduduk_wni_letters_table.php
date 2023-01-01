<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditFieldBiodataPendudukWniLettersTable extends Migration
{
    public function up()
    {
        Schema::table('biodata_penduduk_wni_letters', function (Blueprint $table) {
            $table->integer("order")->default(1)->after("id");
        });
    }
    public function down()
    {
        Schema::table('biodata_penduduk_wni_letters', function (Blueprint $table) {
            $table->dropColumn("order");
        });
    }
}
