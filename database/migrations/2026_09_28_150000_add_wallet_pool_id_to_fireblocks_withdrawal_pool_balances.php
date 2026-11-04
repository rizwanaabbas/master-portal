<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fireblocks_withdrawal_pool_balances', function (Blueprint $table) {
            if (!Schema::hasColumn('fireblocks_withdrawal_pool_balances', 'wallet_pool_id')) {
                $table->string('wallet_pool_id', 64)->nullable()->after('vault_account_id')->index();
            }
            if (!Schema::hasColumn('fireblocks_withdrawal_pool_balances', 'balance_source')) {
                $table->string('balance_source', 32)->default('utxo_vault')->after('wallet_pool_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_withdrawal_pool_balances', function (Blueprint $table) {
            if (Schema::hasColumn('fireblocks_withdrawal_pool_balances', 'balance_source')) {
                $table->dropColumn('balance_source');
            }
            if (Schema::hasColumn('fireblocks_withdrawal_pool_balances', 'wallet_pool_id')) {
                $table->dropColumn('wallet_pool_id');
            }
        });
    }
};
