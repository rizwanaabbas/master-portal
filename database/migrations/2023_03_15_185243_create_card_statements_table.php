<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardStatementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_statements', function (Blueprint $table) {
            $table->id();
            $table->string('mpin');
            $table->string('terminal_owner_country')->nullable();
            $table->string('terminal_type')->nullable();
            $table->string('processing_currency')->nullable();
            $table->string('debit_credit')->nullable();
            $table->string('type')->nullable();
            $table->string('result')->nullable();
            $table->string('txn_id')->nullable();
            $table->string('card_holder')->nullable();
            $table->string('visa_reference_no')->nullable();
            $table->string('base_amount')->nullable();
            $table->string('cardholder_fee')->nullable();
            $table->string('terminal_id')->nullable();
            $table->string('base_currency')->nullable();
            $table->string('approval_code')->nullable();
            $table->string('card_number')->nullable();
            $table->string('terminal_currency')->nullable();
            $table->string('terminal_amount')->nullable();
            $table->string('MCC')->nullable();
            $table->string('type_of_operation')->nullable();
            $table->string('card_id')->nullable();
            $table->string('is_reversal')->nullable();
            $table->string('processing_amount')->nullable();
            $table->string('card_token')->nullable();
            $table->string('iso_fee')->nullable();
            $table->string('transaction_state')->nullable();
            $table->datetime('time')->nullable();
            $table->string('terminal_owner_title')->nullable();
            $table->string('transaction_nature')->nullable();
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
        Schema::dropIfExists('card_statements');
    }
}
