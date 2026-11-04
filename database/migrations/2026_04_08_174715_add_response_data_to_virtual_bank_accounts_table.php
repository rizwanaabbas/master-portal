<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResponseDataToVirtualBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('virtual_bank_accounts', 'response_data')) {
            Schema::table('virtual_bank_accounts', function (Blueprint $table) {
                $table->json('response_data')->nullable()->after('routing_code_entries');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('virtual_bank_accounts', 'response_data')) {
            Schema::table('virtual_bank_accounts', function (Blueprint $table) {
                $table->dropColumn('response_data');
            });
        }
    }
}