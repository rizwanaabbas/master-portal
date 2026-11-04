<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('networks', function (Blueprint $table) {

            // Drop old unique index
            $table->dropUnique('networks_code_unique');

            // Add new composite unique
            $table->unique(['code', 'is_testnet']);
        });
    }

    public function down(): void
    {
        Schema::table('networks', function (Blueprint $table) {

            $table->dropUnique(['code', 'is_testnet']);

            $table->unique('code');
        });
    }
};