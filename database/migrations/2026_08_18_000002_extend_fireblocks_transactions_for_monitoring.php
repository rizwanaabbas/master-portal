<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('fireblocks_transactions', 'sub_status')) {
                $table->string('sub_status')->nullable()->index()->after('status');
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'internal_status')) {
                $table->string('internal_status')->nullable()->index()->after('sub_status');
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'monitoring_state')) {
                $table->string('monitoring_state')->default('normal')->index()->after('internal_status');
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'direction')) {
                $table->string('direction')->nullable()->index()->after('monitoring_state');
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'operation')) {
                $table->string('operation')->nullable()->after('direction');
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'stuck_detected_at')) {
                $table->timestamp('stuck_detected_at')->nullable()->after('operation');
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'last_polled_at')) {
                $table->timestamp('last_polled_at')->nullable()->after('stuck_detected_at');
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'last_webhook_at')) {
                $table->timestamp('last_webhook_at')->nullable()->after('last_polled_at');
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'auto_action_count')) {
                $table->unsignedTinyInteger('auto_action_count')->default(0)->after('last_webhook_at');
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'root_fireblocks_tx_id')) {
                $table->string('root_fireblocks_tx_id')->nullable()->index()->after('auto_action_count');
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'replaced_by_fireblocks_tx_id')) {
                $table->string('replaced_by_fireblocks_tx_id')->nullable()->index()->after('root_fireblocks_tx_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            $columns = [
                'sub_status', 'internal_status', 'monitoring_state', 'direction', 'operation',
                'stuck_detected_at', 'last_polled_at', 'last_webhook_at', 'auto_action_count',
                'root_fireblocks_tx_id', 'replaced_by_fireblocks_tx_id',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('fireblocks_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
