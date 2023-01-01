<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditFieldSkpwniLettersTable extends Migration
{
    public function up()
    {
        Schema::table('skpwni_letters', function (Blueprint $table) {
            $table->integer("order")->default(1)->after("id");
            $table->integer("order_desa")->default(1)->after("order");
        });
    }
    public function down()
    {
        Schema::table('skpwni_letters', function (Blueprint $table) {
            $table->dropColumn("order");
            $table->dropColumn("order_desa");
        });
    }
}
