<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldUsedAsToDtksSekolahLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sktm_dtks_letters', function (Blueprint $table) {
            $table->string('used_as')->after('kartu_keluarga');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sktm_dtks_letters', function (Blueprint $table) {
            $table->dropColumn('used_as');
        });
    }
}
