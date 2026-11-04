<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('fireblocks_transactions', 'is_sweep')) {
                $table->boolean('is_sweep')->default(false)->after('operation');
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'sweep_job_id')) {
                $table->unsignedBigInteger('sweep_job_id')->nullable()->after('is_sweep');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('fireblocks_transactions', 'sweep_job_id')) {
                $table->dropColumn('sweep_job_id');
            }
            if (Schema::hasColumn('fireblocks_transactions', 'is_sweep')) {
                $table->dropColumn('is_sweep');
            }
        });
    }
};
