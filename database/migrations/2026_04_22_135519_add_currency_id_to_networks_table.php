<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('networks', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('id');

            // Optional index (recommended)
            $table->index('currency_id');
        });
    }

    public function down(): void
    {
        Schema::table('networks', function (Blueprint $table) {
            $table->dropIndex(['currency_id']);
            $table->dropColumn('currency_id');
        });
    }
};
