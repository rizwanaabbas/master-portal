<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('virtual_bank_accounts', function (Blueprint $table) {
            $table->json('account_info')->nullable()->after('routing_code_entries');
        });
    }

    public function down(): void
    {   
        Schema::table('virtual_bank_accounts', function (Blueprint $table) {
            $table->dropColumn('account_info');
        });
    }
};