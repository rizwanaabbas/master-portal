<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('fireblocks_sweep_rules', 'sweep_amount_source')) {
                $table->string('sweep_amount_source', 32)
                    ->default('available')
                    ->after('reserve_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            if (Schema::hasColumn('fireblocks_sweep_rules', 'sweep_amount_source')) {
                $table->dropColumn('sweep_amount_source');
            }
        });
    }
};
