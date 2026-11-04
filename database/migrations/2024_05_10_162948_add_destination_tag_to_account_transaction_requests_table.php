<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDestinationTagToAccountTransactionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_transaction_requests', function (Blueprint $table) {
            $table->string('destination_tag', 100)->nullable()->after('to_wallet_address');
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
            $table->dropColumn('destination_tag');
        });
    }
}
