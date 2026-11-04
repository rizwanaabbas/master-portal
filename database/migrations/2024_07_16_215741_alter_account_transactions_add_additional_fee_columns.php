<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAccountTransactionsAddAdditionalFeeColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->integer('fee_debit_account_id')->after('account_id')->nullable();
            $table->integer('parent_trn_id')->after('link_tr_id')->nullable();
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
            $table->dropColumn('fee_debit_account_id');
            $table->dropColumn('parent_trn_id');
        });
    }
}
