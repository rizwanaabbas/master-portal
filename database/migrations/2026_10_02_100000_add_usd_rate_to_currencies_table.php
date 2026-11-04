<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('currencies') || Schema::hasColumn('currencies', 'usd_rate')) {
            return;
        }

        Schema::table('currencies', function (Blueprint $table) {
            $table->decimal('usd_rate', 24, 8)->nullable()->after('is_active');
            $table->timestamp('usd_rate_at')->nullable()->after('usd_rate');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('currencies') || !Schema::hasColumn('currencies', 'usd_rate')) {
            return;
        }

        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn(['usd_rate', 'usd_rate_at']);
        });
    }
};
