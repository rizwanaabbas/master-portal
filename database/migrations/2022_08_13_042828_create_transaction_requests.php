<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_transaction_requests', function (Blueprint $table) {
            $table->id();
            $table->integer("account_id")->nullable();
            $table->integer("link_tr_id")->nullable();
            $table->integer("beneficiary_id")->nullable();
            $table->integer("currency_id")->nullable();
            $table->string("transaction_type")->nullable();
            $table->string("transaction_nature")->nullable();
            $table->datetime("transaction_date")->nullable();
            $table->double("amount")->nullable();
            $table->double("ex_rate")->nullable();

            $table->string("corresponding_bank_name")->nullable();
            $table->string("corresponding_bank_swift_code")->nullable();
            $table->text("corresponding_bank_address")->nullable();

            $table->string("status")->nullable();
            $table->text("remarks")->nullable();

            $table->datetime("created_at")->nullable();
            $table->datetime("updated_at")->nullable();
            $table->datetime("approved_at")->nullable();
            $table->datetime("rejected_at")->nullable()
            ;
            $table->integer("created_by")->nullable();
            $table->integer("updated_by")->nullable();
            $table->integer("approved_by")->nullable();
            $table->integer("rejected_by")->nullable();

            $table->text("reject_reason")->nullable();

        });
    }



    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_transaction_requests');
    }
}
