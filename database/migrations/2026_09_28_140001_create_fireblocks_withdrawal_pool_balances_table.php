<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fireblocks_withdrawal_pool_balances', function (Blueprint $table) {
            $table->id();
            $table->string('vault_account_id', 64)->index();
            $table->string('asset_id', 64)->index();
            $table->unsignedBigInteger('portal_asset_id')->nullable()->index();
            $table->unsignedBigInteger('currency_id')->nullable()->index();
            $table->decimal('total', 36, 18)->default(0);
            $table->decimal('available', 36, 18)->default(0);
            $table->decimal('pending', 36, 18)->default(0);
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->unique(['vault_account_id', 'asset_id'], 'fb_withdraw_pool_bal_vault_asset_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fireblocks_withdrawal_pool_balances');
    }
};
