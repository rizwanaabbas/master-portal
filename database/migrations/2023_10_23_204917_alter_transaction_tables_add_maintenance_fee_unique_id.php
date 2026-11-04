<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTransactionTablesAddMaintenanceFeeUniqueId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->string('maintenance_fee_unique_id')->after('link_tr_id')->nullable();
        });

        Schema::table('account_transaction_requests', function (Blueprint $table) {
            $table->string('maintenance_fee_unique_id')->after('link_tr_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
