<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFamilyMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->string("mother_nik")->nullable();
            $table->string("mother_name")->nullable();
            $table->string("father_nik")->nullable();
            $table->string("father_name")->nullable();
            $table->string("blood_type")->nullable();
            $table->string("family_status")->nullable();
            $table->string("last_study")->nullable();
            $table->string("physical_mental_disorders")->nullable();
            $table->string("disabilities")->nullable();
            $table->string("paspor_number")->nullable();
            $table->date("paspor_due_date")->nullable();
            $table->string("birth_certificate_number")->nullable();
            $table->string("marriage_certificate_number")->nullable();
            $table->date("marriage_date")->nullable();
            $table->string("divorce_certificate_number")->nullable();
            $table->date("divorce_date")->nullable();
            $table->boolean("head_of_family")->default(false);
            $table->unsignedBigInteger('resident_id');
            $table->unsignedBigInteger('biodata_penduduk_wni_letter_id');
            $table->timestamps();

            $table->foreign('resident_id')->references('id')->on('residents');
            $table->foreign('biodata_penduduk_wni_letter_id')->references('id')->on('biodata_penduduk_wni_letters');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('family_members');
    }
}
