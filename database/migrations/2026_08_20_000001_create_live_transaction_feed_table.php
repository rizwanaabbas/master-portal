<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_transaction_feed', function (Blueprint $table) {
            $table->id();
            $table->string('stream_key', 120)->unique();
            $table->unsignedBigInteger('tr_request_id')->nullable()->index();
            $table->unsignedBigInteger('account_transaction_id')->nullable()->index();
            $table->string('fireblocks_tx_id', 64)->nullable()->index();
            $table->unsignedBigInteger('liquidity_order_id')->nullable()->index();
            $table->string('portal_source', 80)->nullable();
            $table->string('flow_from', 255)->nullable();
            $table->string('flow_to', 255)->nullable();
            $table->string('transaction_nature', 60)->nullable();
            $table->string('transaction_type', 30)->nullable();
            $table->string('asset_code', 40)->nullable();
            $table->string('asset_name', 120)->nullable();
            $table->string('network_name', 120)->nullable();
            $table->string('amount_display', 80)->nullable();
            $table->string('fiat_display', 80)->nullable();
            $table->string('status_label', 80)->nullable();
            $table->string('status_tone', 20)->default('pending');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('ledger_url', 500)->nullable();
            $table->string('initiator_name', 120)->nullable();
            $table->string('client_name', 160)->nullable();
            $table->string('account_label', 160)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_transaction_feed');
    }
};
