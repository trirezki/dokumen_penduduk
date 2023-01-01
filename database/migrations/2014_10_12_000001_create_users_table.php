<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('logo')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('type')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('sub_district')->nullable();
            $table->enum('type_village', ['Kelurahan', 'Desa']);
            $table->string('village')->nullable();
            $table->string('street')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('email_verified_at')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
