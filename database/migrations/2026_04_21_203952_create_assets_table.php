<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            // Plain columns (NO FK)
            $table->unsignedBigInteger('currency_id');
            $table->unsignedBigInteger('network_id');

            $table->string('asset_id')->unique(); // BTC, USDT_ETH, etc.
            $table->boolean('is_testnet')->default(false);

            $table->enum('type', ['utxo', 'account', 'token'])->nullable();

            $table->timestamps();

            // Indexes (important since no FK)
            $table->index('currency_id');
            $table->index('network_id');

            // Optional uniqueness
            $table->unique(['currency_id', 'network_id', 'is_testnet']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};

   