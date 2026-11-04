<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::rename('b2c2_balances', 'liquidity_balances');
        Schema::rename('b2c2_instruments', 'liquidity_instruments');
        Schema::rename('b2c2_rfqs', 'liquidity_rfqs');
        Schema::rename('b2c2_orders', 'liquidity_orders');
        Schema::rename('b2c2_transaction_logs', 'liquidity_transaction_logs');

        Schema::table('liquidity_balances', function (Blueprint $table) {
            $table->string('provider')->default('b2c2')->after('id');
        });
        Schema::table('liquidity_instruments', function (Blueprint $table) {
            $table->string('provider')->default('b2c2')->after('id');
        });
        Schema::table('liquidity_rfqs', function (Blueprint $table) {
            $table->string('provider')->default('b2c2')->after('id');
        });
        Schema::table('liquidity_orders', function (Blueprint $table) {
            $table->string('provider')->default('b2c2')->after('id');
        });
        Schema::table('liquidity_transaction_logs', function (Blueprint $table) {
            $table->string('provider')->default('b2c2')->after('id');
        });

        // Backfill existing rows explicitly (default handles new inserts, this covers old rows on DBs where default isn't retroactive).
        DB::table('liquidity_balances')->update(['provider' => 'b2c2']);
        DB::table('liquidity_instruments')->update(['provider' => 'b2c2']);
        DB::table('liquidity_rfqs')->update(['provider' => 'b2c2']);
        DB::table('liquidity_orders')->update(['provider' => 'b2c2']);
        DB::table('liquidity_transaction_logs')->update(['provider' => 'b2c2']);

        // b2c2_balances never had a unique index on `currency` — just add the new one.
        Schema::table('liquidity_balances', function (Blueprint $table) {
            $table->unique(['provider', 'currency']);
        });

        // MySQL doesn't rename indexes on RENAME TABLE, so the old b2c2_*-prefixed
        // index names are still in place on the renamed tables.
        Schema::table('liquidity_instruments', function (Blueprint $table) {
            $table->dropUnique('b2c2_instruments_instrument_unique');
            $table->unique(['provider', 'instrument']);
        });

        Schema::table('liquidity_rfqs', function (Blueprint $table) {
            $table->dropUnique('b2c2_rfqs_rfq_id_unique');
            $table->unique(['provider', 'rfq_id']);
        });

        Schema::table('liquidity_orders', function (Blueprint $table) {
            $table->dropUnique('b2c2_orders_order_id_unique');
            $table->unique(['provider', 'order_id']);
        });
    }

    public function down()
    {
        Schema::table('liquidity_balances', function (Blueprint $table) {
            $table->dropUnique(['provider', 'currency']);
            $table->dropColumn('provider');
        });

        Schema::table('liquidity_instruments', function (Blueprint $table) {
            $table->dropUnique(['provider', 'instrument']);
            $table->unique(['instrument']);
            $table->dropColumn('provider');
        });

        Schema::table('liquidity_rfqs', function (Blueprint $table) {
            $table->dropUnique(['provider', 'rfq_id']);
            $table->unique(['rfq_id']);
            $table->dropColumn('provider');
        });

        Schema::table('liquidity_orders', function (Blueprint $table) {
            $table->dropUnique(['provider', 'order_id']);
            $table->unique(['order_id']);
            $table->dropColumn('provider');
        });

        Schema::table('liquidity_transaction_logs', function (Blueprint $table) {
            $table->dropColumn('provider');
        });

        Schema::rename('liquidity_balances', 'b2c2_balances');
        Schema::rename('liquidity_instruments', 'b2c2_instruments');
        Schema::rename('liquidity_rfqs', 'b2c2_rfqs');
        Schema::rename('liquidity_orders', 'b2c2_orders');
        Schema::rename('liquidity_transaction_logs', 'b2c2_transaction_logs');
    }
};
