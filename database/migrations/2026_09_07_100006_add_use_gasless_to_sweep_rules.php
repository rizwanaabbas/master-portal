<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            $table->boolean('use_gasless')->default(false)->after('feed_trx_amount');
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            $table->dropColumn('use_gasless');
        });
    }
};
