<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditResidentsTable extends Migration
{
    public function up()
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->after('address')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }
    public function down()
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn("user_id");
        });
    }
}
