<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('fireblocks_sweep_jobs')) {
            return;
        }

        Schema::table('fireblocks_sweep_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('fireblocks_sweep_jobs', 'fireblocks_status')) {
                $table->string('fireblocks_status', 64)->nullable()->after('fireblocks_tx_id');
            }
            if (!Schema::hasColumn('fireblocks_sweep_jobs', 'fireblocks_sub_status')) {
                $table->string('fireblocks_sub_status', 64)->nullable()->after('fireblocks_status');
            }
            if (!Schema::hasColumn('fireblocks_sweep_jobs', 'fireblocks_status_updated_at')) {
                $table->timestamp('fireblocks_status_updated_at')->nullable()->after('fireblocks_sub_status');
            }
            if (!Schema::hasColumn('fireblocks_sweep_jobs', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('fireblocks_status_updated_at');
            }
            if (!Schema::hasColumn('fireblocks_sweep_jobs', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('completed_at');
            }
        });

        Schema::table('fireblocks_sweep_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('fireblocks_sweep_jobs', 'fireblocks_tx_id')) {
                $table->index('fireblocks_tx_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fireblocks_sweep_jobs')) {
            return;
        }

        Schema::table('fireblocks_sweep_jobs', function (Blueprint $table) {
            foreach ([
                'failed_at',
                'completed_at',
                'fireblocks_status_updated_at',
                'fireblocks_sub_status',
                'fireblocks_status',
            ] as $column) {
                if (Schema::hasColumn('fireblocks_sweep_jobs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
