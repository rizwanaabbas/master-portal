<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Modify column to nullable using raw SQL
        DB::statement('ALTER TABLE fireblocks_transactions MODIFY source_vault_account_id VARCHAR(255) NULL;');

        // 2. Add the new column
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            $table->string('wallet_pool_id')->nullable()->after('source_vault_account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            $table->dropColumn('wallet_pool_id');
        });

        DB::statement('ALTER TABLE fireblocks_transactions MODIFY source_vault_account_id VARCHAR(255) NOT NULL;');
    }
};
