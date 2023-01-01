<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResidentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->string('nik');
            $table->string('name');
            $table->enum('gender', ['laki-laki', 'perempuan']);
            $table->string('place_of_birth');
            $table->date('date_of_birth');
            $table->enum('religion', ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha', 'Konghucu']);
            $table->string('profession');
            $table->enum('marital_status', ['jejaka', 'perawan', 'kawin', 'cerai_hidup', 'cerai_mati']);
            $table->string('address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('residents');
    }
}
