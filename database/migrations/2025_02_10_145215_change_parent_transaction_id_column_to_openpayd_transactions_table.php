<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeParentTransactionIdColumnToOpenpaydTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('openpayd_transactions', function (Blueprint $table) {
            // change parent_account_id from string to BigInt.
            $table->unsignedBigInteger('parent_transaction_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('openpayd_transactions', function (Blueprint $table) {
            // Revert back to string if needed
            $table->string('parent_transaction_id')->change(); 
        });
    }
}
