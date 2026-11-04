<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDecimalsToCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            // unsignedTinyInteger covers 0–255 (crypto decimals are usually 0–18)
            $table->unsignedTinyInteger('decimals')
                ->nullable()
                ->after('max_amount'); // Adjust placement to fit your schema
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn('decimals');
        });
    }
}
