<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOpenpaydAccountIdToAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Add the column to store the OpenPayd account ID
            $table->string('openpayd_account_id')->nullable()->after('account_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Drop the column to store the OpenPayd account ID
            $table->dropColumn('openpayd_account_id');
        });
    }
}
