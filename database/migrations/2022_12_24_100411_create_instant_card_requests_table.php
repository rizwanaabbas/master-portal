<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstantCardRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instant_card_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unique();
            $table->string('firstName');
            $table->string('lastName');
            $table->string('display_name');
            $table->string('email');
            $table->string('mobileNo');
            $table->string('dateOfBirth');
            $table->string('gender');
            $table->string('countryCode');
            $table->string('callingCode');
            $table->string('state');
            $table->string('city');
            $table->string('postalCode');
            $table->text('address');
            $table->boolean('instant_card');
            $table->string('status');
            $table->string('assigned_username_after_create_user_api')->nullable();
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
        Schema::dropIfExists('instant_card_requests');
    }
}
