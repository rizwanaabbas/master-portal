<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_bank_transactions', function (Blueprint $table) {
            $table->string('provider_name')
                  ->default('openpayd')
                  ->after('virtual_bank_transaction_id');
        });

        // Optional: ensure existing rows get default explicitly (older MySQL safety)
        DB::table('virtual_bank_transactions')
            ->whereNull('provider_name')
            ->update(['provider_name' => 'openpayd']);
    }

    public function down(): void
    {
        Schema::table('virtual_bank_transactions', function (Blueprint $table) {
            $table->dropColumn('provider_name');
        });
    }
};
