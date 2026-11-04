<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            $table->string('gas_funding_vault_id', 64)->nullable()->after('target_asset_id');
            $table->decimal('feed_trx_amount', 36, 18)->nullable()->after('gas_funding_vault_id');
        });

        Schema::table('fireblocks_sweep_jobs', function (Blueprint $table) {
            $table->string('gas_asset_id', 128)->nullable()->after('asset_id');
            $table->decimal('gas_required_amount', 36, 18)->nullable()->after('amount');
            $table->decimal('gas_feed_amount', 36, 18)->nullable()->after('gas_required_amount');
            $table->string('gas_funding_vault_id', 64)->nullable()->after('gas_feed_amount');
            $table->string('gas_feed_fireblocks_tx_id')->nullable()->after('fireblocks_tx_id');
            $table->string('gas_feed_external_tx_id')->nullable()->after('gas_feed_fireblocks_tx_id');
            $table->decimal('planned_sweep_amount', 36, 18)->nullable()->after('gas_feed_external_tx_id');

            $table->index('gas_feed_fireblocks_tx_id');
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_sweep_jobs', function (Blueprint $table) {
            $table->dropIndex(['gas_feed_fireblocks_tx_id']);
            $table->dropColumn([
                'gas_asset_id',
                'gas_required_amount',
                'gas_feed_amount',
                'gas_funding_vault_id',
                'gas_feed_fireblocks_tx_id',
                'gas_feed_external_tx_id',
                'planned_sweep_amount',
            ]);
        });

        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            $table->dropColumn(['gas_funding_vault_id', 'feed_trx_amount']);
        });
    }
};
