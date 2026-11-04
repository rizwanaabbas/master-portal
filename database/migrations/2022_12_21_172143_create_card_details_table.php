<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_details', function (Blueprint $table) {
            $table->id();
            $table->string('cardNumber')->nullable();
            $table->text('cardImage')->nullable();
            $table->string('programType')->nullable();
            $table->string('expiryDate')->nullable();
            $table->string('cardStatus')->nullable();
            $table->text('remarks')->nullable();
            $table->string('cardHolderName')->nullable();
            $table->string('mpin')->nullable();
            $table->string('max_balance_limit')->nullable();
            $table->string('currency')->nullable();
            $table->string('cType')->nullable();
            $table->string('firstName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('userName')->nullable();
            $table->string('email')->nullable();
            $table->string('mobileNo')->nullable();
            $table->string('countryCode')->nullable();
            $table->string('dateOfBirth')->nullable();
            $table->string('callingCode')->nullable();
            $table->string('gender')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('postalCode')->nullable();
            $table->text('id_proof_front')->nullable();
            $table->text('id_proof_back')->nullable();
            $table->text('address_proof_front')->nullable();
            $table->text('address_proof_back')->nullable();
            $table->string('id_proof_submission_date')->nullable();
            $table->string('address_proof_submission_date')->nullable();
            $table->string('kyc_remark')->nullable();
            $table->string('kyc_remark_date')->nullable();
            $table->string('cardUserStatus')->nullable();
            $table->string('merchant_id')->nullable();
            $table->string('processor_message')->nullable();
            $table->string('wallet_id')->nullable();
            $table->string('unique_id')->nullable();
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
        Schema::dropIfExists('card_details');
    }
}
