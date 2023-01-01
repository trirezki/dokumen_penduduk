<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldPendukungToDamiuLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('damiu_letters', function (Blueprint $table) {
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
        Schema::table('damiu_letters', function (Blueprint $table) {
            $table->dropColumn('file_pendukung');
        });
    }
}
