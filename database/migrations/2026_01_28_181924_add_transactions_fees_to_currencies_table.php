<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransactionsFeesToCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('currencies', function (Blueprint $table) {
            // add transaction_fee column
            $table->double('transaction_fee_rate')->nullable()->after('bitpace_address');
            $table->double('bank_fee_rate')->nullable()->after('bitpace_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('currencies', function (Blueprint $table) {
            // remove tansaction fee column
            $table->dropColumn('transaction_fee_rate');
            $table->dropColumn('bank_fee_rate');
        });
    }
}
