<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditFieldSktmDtksLettersTable extends Migration
{
    public function up()
    {
        Schema::table('sktm_dtks_letters', function (Blueprint $table) {
            $table->string('file_arsip')->nullable()->after("verified_file");
            $table->integer("order")->default(1)->after("id");
            $table->integer("order_desa")->default(1)->after("order");
        });
    }
    public function down()
    {
        Schema::table('sktm_dtks_letters', function (Blueprint $table) {
            $table->dropColumn("file_arsip");
            $table->dropColumn("order");
            $table->dropColumn("order_desa");
        });
    }
}
