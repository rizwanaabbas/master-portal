<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToOpenpaydBankAccount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('openpayd_bank_account', function (Blueprint $table) {
            // adding account_id column to openpayd_bank_account table
            $table->string('account_id')->after('id');
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
            // removing account_id column from openpayd_bank_account table
            $table->dropColumn(['account_id']);
        });
    }
}
