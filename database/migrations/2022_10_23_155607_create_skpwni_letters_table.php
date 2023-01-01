<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSkpwniLettersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('skpwni_letters', function (Blueprint $table) {
            $table->id();
            $table->string('prefix');
            $table->string('suffix');
            $table->string('prefix_desa');
            $table->string('suffix_desa');
            $table->string("family_card_number")->nullable();
            $table->string("rt")->nullable();
            $table->string("rw")->nullable();
            $table->string("village")->nullable();
            $table->string("sub_district")->nullable();
            $table->string("district")->nullable();
            $table->string("province")->nullable();
            $table->string("zip_code")->nullable();
            $table->string("phone")->nullable();
            $table->string("reason_to_move")->nullable();
            $table->string("moving_destination")->nullable();
            $table->string("moving_destination_rt")->nullable();
            $table->string("moving_destination_rw")->nullable();
            $table->string("moving_destination_village")->nullable();
            $table->string("moving_destination_sub_district")->nullable();
            $table->string("moving_destination_district")->nullable();
            $table->string("moving_destination_province")->nullable();
            $table->string("moving_destination_zip_code")->nullable();
            $table->string("moving_destination_phone")->nullable();
            $table->string("move_classification")->nullable();
            $table->string("type_of_move")->nullable();
            $table->string("status_not_move")->nullable();
            $table->string("status_move")->nullable();
            $table->string("moving_date_plan")->nullable();
            $table->string('verified_file')->nullable();
            $table->unsignedBigInteger('kepala_keluarga_id');
            $table->unsignedBigInteger('penandatangan_kecamatan_id');
            $table->unsignedBigInteger('penandatangan_desa_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('kepala_keluarga_id')->references('id')->on('residents');
            $table->foreign('penandatangan_kecamatan_id')->references('id')->on('letter_officials');
            $table->foreign('penandatangan_desa_id')->references('id')->on('letter_officials');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('skpwni_letters');
    }
}
