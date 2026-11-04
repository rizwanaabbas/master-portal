<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterVirtualBankAccountsTableAddZenusFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('virtual_bank_accounts', function (Blueprint $table) {
            $table->json('account_numbers')->after('domestic');
            // $table->json('response_data')->after('domestic');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('virtual_bank_accounts', function (Blueprint $table) {
            $table->dropColumn('account_numbers');
            // $table->dropColumn('response_data');
        });
    }
}
