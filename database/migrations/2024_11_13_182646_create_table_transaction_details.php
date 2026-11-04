<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableTransactionDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('trn_id');
            $table->double('amount')->nullable();
            $table->double('crypto_amount')->nullable();
            $table->double('ex_rate')->nullable();
            $table->datetime('transaction_date')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('zeroize')->default(0);
            $table->integer('service_fee_client_account_id');
            $table->integer('service_fee_bank_account_id');
            $table->integer('service_fee_currency_id');
            $table->double('service_fee')->default(0);
            $table->text('service_fee_remarks')->nullable();
            $table->boolean('is_service_fee_trn_hidden_for_client')->default(0);
            $table->integer('trn_fee_client_account_id')->nullable();
            $table->integer('trn_fee_bank_account_id')->nullable();
            $table->integer('trn_fee_currency_id')->nullable();
            $table->double('trn_fee')->default(0)->nullable();
            $table->text('trn_fee_remarks')->nullable();
            $table->boolean('is_trn_fee_trn_hidden_for_client')->default(0);
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
        Schema::dropIfExists('transaction_details');
    }
}
