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
            if (Schema::hasColumn('fireblocks_sweep_jobs', 'skip_reason')) {
                $table->text('skip_reason')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fireblocks_sweep_jobs')) {
            return;
        }

        Schema::table('fireblocks_sweep_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('fireblocks_sweep_jobs', 'skip_reason')) {
                $table->string('skip_reason')->nullable()->change();
            }
        });
    }
};
