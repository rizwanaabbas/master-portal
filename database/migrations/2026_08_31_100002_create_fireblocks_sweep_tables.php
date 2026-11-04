<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fireblocks_sweep_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('enabled')->default(true);
            $table->string('source_type', 32)->default('client_deposit_vaults');
            $table->text('source_vault_ids')->nullable();
            $table->string('destination_vault_id');
            $table->text('asset_ids')->nullable();
            $table->decimal('min_balance', 36, 18)->default(0);
            $table->decimal('min_balance_usd', 18, 8)->nullable();
            $table->decimal('reserve_amount', 36, 18)->default(0);
            $table->string('trigger_modes', 64)->default('deposit,scheduled');
            $table->unsignedInteger('deposit_delay_minutes')->default(0);
            $table->string('fee_level', 16)->default('LOW');
            $table->decimal('max_fee_usd', 18, 8)->nullable();
            $table->boolean('skip_if_fee_high')->default(false);
            $table->boolean('sweep_tokens_before_base')->default(false);
            $table->boolean('dry_run')->default(false);
            $table->timestamps();
        });

        Schema::create('fireblocks_sweep_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->nullable()->constrained('fireblocks_sweep_rules')->nullOnDelete();
            $table->string('source_vault_id');
            $table->string('destination_vault_id');
            $table->string('asset_id');
            $table->string('trigger_source', 32);
            $table->string('deposit_fireblocks_tx_id')->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('skip_reason')->nullable();
            $table->string('fireblocks_tx_id')->nullable();
            $table->decimal('amount', 36, 18)->nullable();
            $table->decimal('fee_estimate', 36, 18)->nullable();
            $table->string('external_tx_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['source_vault_id', 'asset_id', 'status']);
            $table->index('status');
        });

        Schema::create('fireblocks_sweep_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sweep_job_id')->nullable()->constrained('fireblocks_sweep_jobs')->nullOnDelete();
            $table->string('source_vault_id')->nullable();
            $table->string('asset_id')->nullable();
            $table->string('level', 16)->default('info');
            $table->string('event', 64);
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fireblocks_sweep_logs');
        Schema::dropIfExists('fireblocks_sweep_jobs');
        Schema::dropIfExists('fireblocks_sweep_rules');
    }
};
