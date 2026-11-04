<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('fireblocks_transaction_alerts')) {
            Schema::create('fireblocks_transaction_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('fireblocks_tx_id')->nullable()->index('fb_tx_alerts_tx_id_idx');
                $table->string('event_type')->default('transaction.alert.stuck');
                $table->string('issue_type')->nullable();
                $table->string('severity')->nullable();
                $table->string('vault_account_id')->nullable();
                $table->string('base_asset')->nullable();
                $table->unsignedInteger('blocked_transaction_count')->nullable();
                $table->unsignedInteger('stuck_duration_seconds')->nullable();
                $table->json('recommended_action')->nullable();
                $table->json('payload')->nullable();
                $table->string('status')->default('open')->index('fb_tx_alerts_status_idx');
                $table->string('action_taken')->nullable();
                $table->timestamp('action_taken_at')->nullable();
                $table->unsignedBigInteger('dismissed_by')->nullable();
                $table->timestamp('dismissed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('fireblocks_transaction_monitoring_logs')) {
            Schema::create('fireblocks_transaction_monitoring_logs', function (Blueprint $table) {
                $table->id();
                $table->string('fireblocks_tx_id')->nullable()->index('fb_tx_monitor_logs_tx_id_idx');
                $table->string('action')->index('fb_tx_monitor_logs_action_idx');
                $table->string('trigger_source')->default('system');
                $table->string('previous_status')->nullable();
                $table->string('new_status')->nullable();
                $table->string('previous_sub_status')->nullable();
                $table->string('new_sub_status')->nullable();
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->boolean('success')->default(false);
                $table->text('error_message')->nullable();
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->string('idempotency_key')->nullable()->index('fb_tx_monitor_logs_idem_idx');
                $table->timestamps();

                $table->index(['fireblocks_tx_id', 'created_at'], 'fb_tx_monitor_logs_tx_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fireblocks_transaction_monitoring_logs');
        Schema::dropIfExists('fireblocks_transaction_alerts');
    }
};
