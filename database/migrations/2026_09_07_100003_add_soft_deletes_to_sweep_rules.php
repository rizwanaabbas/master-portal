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
            if (!Schema::hasColumn('fireblocks_sweep_rules', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fireblocks_sweep_rules')) {
            return;
        }

        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            if (Schema::hasColumn('fireblocks_sweep_rules', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
