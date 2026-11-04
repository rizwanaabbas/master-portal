<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fireblocks_users', function (Blueprint $table) {
            if (!Schema::hasColumn('fireblocks_users', 'badge_color')) {
                $table->string('badge_color', 7)->nullable()->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_users', function (Blueprint $table) {
            if (Schema::hasColumn('fireblocks_users', 'badge_color')) {
                $table->dropColumn('badge_color');
            }
        });
    }
};
