<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('fireblocks_transactions', 'chainalysis_status')) {
                $table->string('chainalysis_status', 64)->nullable()->after('sub_status');
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'chainalysis_scan_url')) {
                $table->string('chainalysis_scan_url', 512)->nullable()->after('chainalysis_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('fireblocks_transactions', 'chainalysis_scan_url')) {
                $table->dropColumn('chainalysis_scan_url');
            }
            if (Schema::hasColumn('fireblocks_transactions', 'chainalysis_status')) {
                $table->dropColumn('chainalysis_status');
            }
        });
    }
};
