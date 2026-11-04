<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const ASSET_ID = 'USDT_TRX_TEST4';

    private const CONTRACT_ADDRESS = 'TG3XXyExBkPp9nzdajDZsozEu4BkaSJozs';

    public function up(): void
    {
        if (!Schema::hasTable('networks') || !Schema::hasTable('assets') || !Schema::hasTable('currencies')) {
            return;
        }

        $usdt = DB::table('currencies')->where('short', 'USDT')->first();
        if ($usdt === null) {
            return;
        }

        $now = now();

        $networkId = DB::table('networks')->where('code', self::ASSET_ID)->value('id');
        if ($networkId === null) {
            $networkId = DB::table('networks')->insertGetId([
                'code' => self::ASSET_ID,
                'name' => 'Tron Testnet',
                'currency_id' => $usdt->id,
                'is_testnet' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (DB::table('assets')->where('asset_id', self::ASSET_ID)->exists()) {
            return;
        }

        DB::table('assets')->insert([
            'currency_id' => $usdt->id,
            'network_id' => $networkId,
            'asset_id' => self::ASSET_ID,
            'is_testnet' => 1,
            'type' => 'token',
            'contract_address' => self::CONTRACT_ADDRESS,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('networks') || !Schema::hasTable('assets')) {
            return;
        }

        DB::table('assets')->where('asset_id', self::ASSET_ID)->delete();
        DB::table('networks')->where('code', self::ASSET_ID)->delete();
    }
};
