<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Renames the production sample rule and adds a matching testnet sample rule.
 *
 * On non-production environments (local/staging): testnet rule enabled, production disabled.
 * On production: production rule enabled, testnet rule disabled.
 */
return new class extends Migration {
    public const PRODUCTION_RULE_NAME = 'Sample — Production — Client deposits → omnibus (non-UTXO)';

    public const LEGACY_RULE_NAME = 'Sample — Client deposits → omnibus (non-UTXO)';

    public const TESTNET_RULE_NAME = 'Sample — Testnet — Client deposits → omnibus (non-UTXO)';

    public function up(): void
    {
        if (!Schema::hasTable('fireblocks_sweep_rules')) {
            return;
        }

        $isProduction = app()->environment('production');

        DB::table('fireblocks_sweep_rules')
            ->where('name', self::LEGACY_RULE_NAME)
            ->update([
                'name' => self::PRODUCTION_RULE_NAME,
                'enabled' => $isProduction,
                'priority' => 200,
                'updated_at' => now(),
            ]);

        if (!DB::table('fireblocks_sweep_rules')->where('name', self::PRODUCTION_RULE_NAME)->exists()) {
            DB::table('fireblocks_sweep_rules')->insert([
                'name' => self::PRODUCTION_RULE_NAME,
                'priority' => 200,
                'enabled' => $isProduction,
                'source_type' => 'client_deposit_vaults',
                'source_vault_ids' => null,
                'destination_vault_id' => '',
                'asset_ids' => implode(', ', [
                    'ETH',
                    'USDT_ERC20',
                    'USDC',
                    'USDT_BSC',
                    'USDC_BSC',
                    'USDC_POLYGON',
                    'BNB_BSC',
                    'TRX',
                    'TRX_USDT_S2UZ',
                    'SOL',
                    'MATIC_POLYGON',
                    'AVAX',
                ]),
                'min_balance' => '0.01',
                'min_balance_usd' => null,
                'reserve_amount' => '0',
                'trigger_modes' => 'deposit,scheduled',
                'deposit_delay_minutes' => 5,
                'fee_level' => 'LOW',
                'max_fee_usd' => null,
                'skip_if_fee_high' => false,
                'sweep_tokens_before_base' => false,
                'dry_run' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('fireblocks_sweep_rules')->where('name', self::TESTNET_RULE_NAME)->exists()) {
            return;
        }

        DB::table('fireblocks_sweep_rules')->insert([
            'name' => self::TESTNET_RULE_NAME,
            'priority' => 100,
            'enabled' => !$isProduction,
            'source_type' => 'client_deposit_vaults',
            'source_vault_ids' => null,
            'destination_vault_id' => '',
            'asset_ids' => implode(', ', [
                'ETH_TEST5',
                'SOL_TEST',
                'TRX_TEST',
                'GUSDT_TEST',
                'USDC_ETH_TEST5_0GER',
                'BNB_TEST',
                'USDT_BSC_TEST',
                'AVAXTEST',
            ]),
            'min_balance' => '0.001',
            'min_balance_usd' => null,
            'reserve_amount' => '0',
            'trigger_modes' => 'deposit,scheduled',
            'deposit_delay_minutes' => 2,
            'fee_level' => 'LOW',
            'max_fee_usd' => null,
            'skip_if_fee_high' => false,
            'sweep_tokens_before_base' => false,
            'dry_run' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('fireblocks_sweep_rules')) {
            return;
        }

        DB::table('fireblocks_sweep_rules')
            ->where('name', self::TESTNET_RULE_NAME)
            ->delete();

        DB::table('fireblocks_sweep_rules')
            ->where('name', self::PRODUCTION_RULE_NAME)
            ->update([
                'name' => self::LEGACY_RULE_NAME,
                'enabled' => true,
                'priority' => 100,
                'updated_at' => now(),
            ]);
    }
};
