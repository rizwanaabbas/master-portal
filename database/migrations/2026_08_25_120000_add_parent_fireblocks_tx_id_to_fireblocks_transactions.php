<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('fireblocks_transactions', 'parent_fireblocks_tx_id')) {
                $table->string('parent_fireblocks_tx_id')->nullable()->index()->after('root_fireblocks_tx_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('fireblocks_transactions', 'parent_fireblocks_tx_id')) {
                $table->dropColumn('parent_fireblocks_tx_id');
            }
        });
    }
};
