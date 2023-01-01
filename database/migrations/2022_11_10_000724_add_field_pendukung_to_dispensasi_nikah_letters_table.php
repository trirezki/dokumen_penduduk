<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldPendukungToDispensasiNikahLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dispensasi_nikah_letters', function (Blueprint $table) {
            $table->string('file_pendukung')->nullable();
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
            $table->dropColumn('file_pendukung');
        });
    }
}
