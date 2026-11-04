<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAccountTransactionRequestTableAddPaymentTypeId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_transaction_requests', function (Blueprint $table) {
            // add payment_type_id column
            $table->unsignedBigInteger('payment_type_id')->nullable()->after('transaction_nature');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_transaction_requests', function (Blueprint $table) {
            // drop payment_type_id column
            $table->dropColumn('payment_type_id');
        });
    }
}
