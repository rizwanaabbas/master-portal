<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('fireblocks_transactions', 'pending_reason')) {
                $table->text('pending_reason')->nullable()->after('error_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('fireblocks_transactions', 'pending_reason')) {
                $table->dropColumn('pending_reason');
            }
        });
    }
};
