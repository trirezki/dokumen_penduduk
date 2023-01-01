<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditFieldOfficialIdToDispensasiNikahLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dispensasi_nikah_letters', function (Blueprint $table) {
            $table->unsignedBigInteger('official_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dispensasi_nikah_letters', function (Blueprint $table) {
            $table->unsignedBigInteger('official_id')->nullable(false)->change();
        });
    }
}
