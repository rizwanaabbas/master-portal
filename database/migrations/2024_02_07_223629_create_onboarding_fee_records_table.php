<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnboardingFeeRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('onboarding_fee_records', function (Blueprint $table) {
            $table->id();
            $table->integer("client_id");
            $table->string('order_id')->nullable();
            $table->string('transaction_status')->nullable();
            $table->double('transaction_amount')->nullable();
            $table->string('currency_code')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('payment_link_url')->nullable();
            $table->string('payment_link_token')->nullable();
            $table->string('transaction_error_description')->nullable();
            $table->string('error_description')->nullable();
            $table->boolean('is_transaction_succeeded')->nullable();
            $table->dateTime('transaction_date')->nullable();
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
        Schema::dropIfExists('onboarding_fee_records');
    }
}
