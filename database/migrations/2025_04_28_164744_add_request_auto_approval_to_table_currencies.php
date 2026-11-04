<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestAutoApprovalToTableCurrencies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('currencies', function (Blueprint $table) {
            // Adding a new column to the currencies table
            $table->boolean('request_auto_approval')->default(false)->after('is_crypto');
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
            // Dropping the column if it exists
            if (Schema::hasColumn('currencies', 'request_auto_approval')) {
                $table->dropColumn('request_auto_approval');
            }
        });
    }
}
