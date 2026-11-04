<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('fireblocks_transactions', 'pending_action')) {
                $table->string('pending_action')->nullable()->index()->after('auto_action_count');
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'last_reattempt_at')) {
                $table->timestamp('last_reattempt_at')->nullable()->index()->after('pending_action');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            foreach (['pending_action', 'last_reattempt_at'] as $column) {
                if (Schema::hasColumn('fireblocks_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
