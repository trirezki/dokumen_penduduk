<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditFieldPenandatanganKecamatanIdToSkpwniLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('skpwni_letters', function (Blueprint $table) {
            $table->unsignedBigInteger('penandatangan_kecamatan_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('skpwni_letters', function (Blueprint $table) {
            $table->unsignedBigInteger('penandatangan_kecamatan_id')->nullable(false)->change();
        });
    }
}
