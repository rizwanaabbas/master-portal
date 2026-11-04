<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sample PRODUCTION sweep rule covering major non-UTXO assets (account + token).
 * See 2026_08_31_100005 for the matching testnet sample rule.
 */
return new class extends Migration {
    public const SAMPLE_RULE_NAME = 'Sample — Production — Client deposits → omnibus (non-UTXO)';

    public function up(): void
    {
        if (!Schema::hasTable('fireblocks_sweep_rules')) {
            return;
        }

        $exists = DB::table('fireblocks_sweep_rules')
            ->where('name', self::SAMPLE_RULE_NAME)
            ->exists();

        if ($exists) {
            return;
        }

        $isProduction = app()->environment('production');

        DB::table('fireblocks_sweep_rules')->insert([
            'name' => self::SAMPLE_RULE_NAME,
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

    public function down(): void
    {
        if (!Schema::hasTable('fireblocks_sweep_rules')) {
            return;
        }

        DB::table('fireblocks_sweep_rules')
            ->where('name', self::SAMPLE_RULE_NAME)
            ->delete();
    }
};
