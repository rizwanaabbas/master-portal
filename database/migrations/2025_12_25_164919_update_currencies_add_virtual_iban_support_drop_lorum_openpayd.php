<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            // Add new column
            $table->string('virtual_iban_provider')->nullable()->default(null);

            // Drop existing columns
            $table->dropColumn(['is_lorum', 'is_openpayd']);
        });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            // Re-add dropped columns (adjust types if different in your schema)
            $table->boolean('is_lorum')->default(false);
            $table->boolean('is_openpayd')->default(false);

            // Remove newly added column
            $table->dropColumn('virtual_iban_provider');
        });
    }
};
