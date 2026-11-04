<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSanctionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //['user_id', 'name', 'entity_type', 'nationality_iso2', 'email', 'citizenship_iso2', 'place_of_birth_iso2', 'country_residence_iso2', 'dob', 'passport_number', 'passport_expiry_date', 'result', 's_response', 'w_response', 'worldCheck', 'sanctions_io']
        Schema::create('sanctions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('user_id');
            $table->string('name');
            $table->string('entity_type');
            $table->string('screen_by')->nullable();
            $table->string('address')->nullable();
            $table->string('gender')->nullable();
            $table->string('passport_issuing_state')->nullable();
            $table->string('nationality_iso2')->nullable();
            $table->string('email')->nullable();
            $table->string('citizenship_iso2')->nullable();
            $table->string('place_of_birth_iso2')->nullable();
            $table->string('country_residence_iso2')->nullable();
            $table->date('dob')->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->boolean('is_suspicious');
            $table->longText('s_response')->nullable();
            $table->longText('w_response')->nullable();
            $table->boolean('worldCheck')->default(0);
            $table->boolean('sanctions_io')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sanctions');
    }
}
