<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_bank_beneficiaries', function (Blueprint $table) {
            $table->string('provider_name', 50)
                  ->default('openpayd')
                  ->after('class');
        });

        // Ensure existing records are populated
        DB::table('virtual_bank_beneficiaries')
            ->whereNull('provider_name')
            ->update(['provider_name' => 'openpayd']);
    }

    public function down(): void
    {
        Schema::table('virtual_bank_beneficiaries', function (Blueprint $table) {
            $table->dropColumn('provider_name');
        });
    }
};
