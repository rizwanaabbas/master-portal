<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            $table->boolean('sweep_orphan_deposits')->default(false)->after('deposit_delay_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            $table->dropColumn('sweep_orphan_deposits');
        });
    }
};
