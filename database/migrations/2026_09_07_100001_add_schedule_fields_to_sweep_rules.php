<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('fireblocks_sweep_rules')) {
            return;
        }

        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('fireblocks_sweep_rules', 'schedule_interval_minutes')) {
                $table->unsignedInteger('schedule_interval_minutes')->nullable()->after('scheduled_min_balance');
            }
            if (!Schema::hasColumn('fireblocks_sweep_rules', 'schedule_run_at')) {
                $table->string('schedule_run_at', 5)->nullable()->after('schedule_interval_minutes');
            }
            if (!Schema::hasColumn('fireblocks_sweep_rules', 'schedule_last_run_at')) {
                $table->timestamp('schedule_last_run_at')->nullable()->after('schedule_run_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fireblocks_sweep_rules')) {
            return;
        }

        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            foreach (['schedule_last_run_at', 'schedule_run_at', 'schedule_interval_minutes'] as $column) {
                if (Schema::hasColumn('fireblocks_sweep_rules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
