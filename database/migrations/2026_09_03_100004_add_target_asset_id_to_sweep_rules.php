<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            $table->string('target_asset_id', 128)->nullable()->after('asset_ids');
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            $table->dropColumn('target_asset_id');
        });
    }
};
