<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientIdToOpenpaydBankAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('openpayd_bank_account', function (Blueprint $table) {
            // add client id to openpayd bank account table
            $table->unsignedBigInteger('client_id')->nullable()->after('account_id');
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
            // drop client id from openpayd bank account table
            $table->dropColumn('client_id');
        });
    }
}
