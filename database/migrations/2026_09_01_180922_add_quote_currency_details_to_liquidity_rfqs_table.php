<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuoteCurrencyDetailsToLiquidityRfqsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('liquidity_rfqs', function (Blueprint $table) {
            // Track which currency the user requested quote in (Fiat vs Crypto)
            $table->unsignedBigInteger('currency_id')->nullable()->after('side');
            $table->string('currency_code', 20)->nullable()->after('currency_id'); // e.g., 'USD', 'XRP', 'EUR'
            $table->enum('quote_currency_type', ['fiat', 'crypto'])->nullable()->after('currency_code');

            // Exact breakdown amounts
            $table->decimal('from_amount', 24, 8)->nullable()->after('price');
            $table->decimal('to_amount', 24, 8)->nullable()->after('from_amount');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('liquidity_rfqs', function (Blueprint $table) {
            $table->dropColumn([
                'currency_id',
                'currency_code',
                'quote_currency_type',
                'from_amount',
                'to_amount',
            ]);
        });
    }
}
