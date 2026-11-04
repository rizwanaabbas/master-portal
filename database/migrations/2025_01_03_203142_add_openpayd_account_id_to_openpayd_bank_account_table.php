<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOpenpaydAccountIdToOpenpaydBankAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('openpayd_bank_account', function (Blueprint $table) {
            $table->string('openpayd_account_id')->nullable()->after('account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('openpayd_bank_account', function (Blueprint $table) {
            $table->dropColumn('openpayd_account_id');
        });
    }
}
