<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crypto_beneficiaries', function (Blueprint $table) {
            $table->string('provider')
                ->default('Fireblocks')
                ->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('crypto_beneficiaries', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};