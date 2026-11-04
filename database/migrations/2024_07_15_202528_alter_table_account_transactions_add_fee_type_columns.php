<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableAccountTransactionsAddFeeTypeColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->boolean('is_service_fee')->after('is_fee_transaction')->default(false);
            $table->boolean('is_trn_fee')->after('is_service_fee')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropColumn('is_service_fee');
            $table->dropColumn('is_trn_fee');
        });
    }
}
