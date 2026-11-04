<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('currency_fee_tiers', function (Blueprint $table) {
            // currency     -> uses currency_id (a specific currency)
            // all_crypto   -> applies to every crypto currency (wildcard)
            // all_fiat     -> applies to every fiat currency (wildcard)
            // A specific-currency rule always overrides the matching type-wildcard.
            $table->enum('currency_scope', ['currency', 'all_crypto', 'all_fiat'])
                ->default('currency')
                ->after('currency_id');
        });
    }

    public function down()
    {
        Schema::table('currency_fee_tiers', function (Blueprint $table) {
            $table->dropColumn('currency_scope');
        });
    }
};
