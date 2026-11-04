<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('b2c2_instruments', function (Blueprint $table) {
            // An instrument is a currency pair (e.g. ADAUSC = ADA -> USC),
            // so a single currency_id cannot represent it.
            if (Schema::hasColumn('b2c2_instruments', 'currency_id')) {
                $table->dropColumn('currency_id');
            }

            $table->unsignedBigInteger('from_currency_id')->nullable()->after('name');
            $table->unsignedBigInteger('to_currency_id')->nullable()->after('from_currency_id');
        });
    }

    public function down()
    {
        Schema::table('b2c2_instruments', function (Blueprint $table) {
            $table->dropColumn(['from_currency_id', 'to_currency_id']);
            $table->unsignedBigInteger('currency_id')->nullable()->after('name');
        });
    }
};
