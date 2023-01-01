<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditFieldOfficialIdToIumkLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('iumk_letters', function (Blueprint $table) {
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
        Schema::table('iumk_letters', function (Blueprint $table) {
            $table->unsignedBigInteger('official_id')->nullable(false)->change();
        });
    }
}
