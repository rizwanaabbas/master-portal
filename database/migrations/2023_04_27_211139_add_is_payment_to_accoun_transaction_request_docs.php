<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPaymentToAccounTransactionRequestDocs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accoun_transaction_request_docs', function (Blueprint $table) {
            $table->boolean("is_payment")->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accoun_transaction_request_docs', function (Blueprint $table) {
            $table->dropColumn("is_payment");
        });
    }
}
