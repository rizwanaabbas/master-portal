<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCryptoTransactionIdColumnToAccountTransactionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_transaction_requests', function (Blueprint $table) {
            Schema::hasColumn('account_transaction_requests', 'crypto_transaction_id') 
            ?: $table->string('crypto_transaction_id')->nullable()->after('transaction_method_id');
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
            Schema::hasColumn('account_transaction_requests', 'crypto_transaction_id') && $table->dropColumn('crypto_transaction_id');
        });
    }
}
