<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFieldEndDateToIumkLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('iumk_letters', function (Blueprint $table) {
            $table->integer("validity_period")->after("end_date");
            $table->dropColumn("end_date");
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
            $table->date("end_date")->after("validity_period");
            $table->dropColumn("validity_period");
        });
    }
}
