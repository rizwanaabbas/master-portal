<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCryptoDetailsIdColumnAccountTransactionRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_transaction_requests', function (Blueprint $table) {
            Schema::hasColumn('account_transaction_requests', 'crypto_details_id')
                ?: $table->string('crypto_details_id')->nullable()->after('crypto_transaction_id');
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
            Schema::hasColumn('account_transaction_requests', 'crypto_details_id') && $table->dropColumn('crypto_details_id');
        });
    }
}
