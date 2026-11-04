<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVendorNameToOpenpaydBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('openpayd_bank_accounts', function (Blueprint $table) {
        //     $table->string('vendor_name')
        //         ->default('openpayd')
        //         ->after('provider'); // adjust position if needed
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('openpayd_bank_accounts', function (Blueprint $table) {
        //     $table->dropColumn('vendor_name');
        // });
    }
}
