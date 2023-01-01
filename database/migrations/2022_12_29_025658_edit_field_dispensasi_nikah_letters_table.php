<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditFieldDispensasiNikahLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dispensasi_nikah_letters', function (Blueprint $table) {
            $table->integer("order")->default(1)->after("id");
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
            $table->dropColumn("order");
        });
    }
}
