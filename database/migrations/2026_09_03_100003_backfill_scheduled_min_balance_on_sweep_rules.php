<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('fireblocks_sweep_rules')) {
            return;
        }

        if (!Schema::hasColumn('fireblocks_sweep_rules', 'scheduled_min_balance')) {
            return;
        }

        // DECIMAL column — do not compare to '' (MySQL error 1292). New column default is 0;
        // this only normalises any unexpected NULLs.
        DB::table('fireblocks_sweep_rules')
            ->whereNull('scheduled_min_balance')
            ->update(['scheduled_min_balance' => 0]);
    }

    public function down(): void
    {
        // Non-destructive data backfill.
    }
};
