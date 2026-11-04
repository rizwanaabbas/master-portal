<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            $table->boolean('treat_as_gross_amount')->default(false)->after('use_gasless');
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_sweep_rules', function (Blueprint $table) {
            $table->dropColumn('treat_as_gross_amount');
        });
    }
};
