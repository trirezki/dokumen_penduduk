<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditFieldNipToOfficialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('officials', function (Blueprint $table) {
            $table->string('nip')->nullable()->change();
            $table->string('rank')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('officials', function (Blueprint $table) {
            $table->string('nip')->nullable(false)->change();
            $table->string('rank')->nullable(false)->change();
        });
    }
}
