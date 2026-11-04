<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('networks', function (Blueprint $table) {
            if (!Schema::hasColumn('networks', 'is_enabled')) {
                $table->boolean('is_enabled')->default(true)->after('is_testnet');
            }
        });

        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'is_enabled')) {
                $table->boolean('is_enabled')->default(true)->after('is_testnet');
            }
        });
    }

    public function down(): void
    {
        Schema::table('networks', function (Blueprint $table) {
            if (Schema::hasColumn('networks', 'is_enabled')) {
                $table->dropColumn('is_enabled');
            }
        });

        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'is_enabled')) {
                $table->dropColumn('is_enabled');
            }
        });
    }
};
