<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('networks', function (Blueprint $table) {
            // Drop unique index on code
            $table->dropUnique('networks_code_is_testnet_unique');
        });
    }

    public function down(): void
    {
        Schema::table('networks', function (Blueprint $table) {
            // Re-add unique constraint if rollback
            $table->unique('code');
        });
    }
};
