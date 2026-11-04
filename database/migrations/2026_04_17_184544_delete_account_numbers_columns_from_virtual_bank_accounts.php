<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteAccountNumbersColumnsFromVirtualBankAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('virtual_bank_accounts', function (Blueprint $table) {
            $table->dropColumn('account_numbers');
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
            $table->string('account_numbers')->nullable()->after('domestic');
        });
    }
}
