<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('b2c2_balances', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('currency');
        });

        Schema::table('b2c2_instruments', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')->nullable()->after('name');
        });
    }

    public function down()
    {
        Schema::table('b2c2_balances', function (Blueprint $table) {
            $table->dropColumn('currency_id');
        });

        Schema::table('b2c2_instruments', function (Blueprint $table) {
            $table->dropColumn('currency_id');
        });
    }
};
