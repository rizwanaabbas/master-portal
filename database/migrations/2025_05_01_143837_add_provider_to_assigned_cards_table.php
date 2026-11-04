<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assigned_cards', function (Blueprint $table) {
            $table->string('provider')->default('vista_money')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('assigned_cards', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};

