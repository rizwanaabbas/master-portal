<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('b2c2_orders', function (Blueprint $table) {
            $table->decimal('sub_total', 24, 8)->nullable()->after('price');      // price * quantity
            $table->decimal('fee', 24, 8)->default(0)->after('sub_total');        // calculated fee amount
            $table->string('fee_type')->nullable()->after('fee');                 // flat | percentage | mixed
            $table->decimal('fee_rate', 18, 8)->nullable()->after('fee_type');    // rate when percentage
            $table->decimal('grand_total', 24, 8)->nullable()->after('fee_rate'); // sub_total + fee

            $table->unsignedBigInteger('fiat_currency_id')->nullable()->after('grand_total');
            $table->unsignedBigInteger('crypto_currency_id')->nullable()->after('fiat_currency_id');

            $table->unsignedBigInteger('tr_request_id')->nullable()->after('crypto_currency_id');
            $table->unsignedBigInteger('dr_tr_id')->nullable()->after('tr_request_id'); // debit account_transaction id
            $table->unsignedBigInteger('cr_tr_id')->nullable()->after('dr_tr_id');       // credit account_transaction id
        });
    }

    public function down()
    {
        Schema::table('b2c2_orders', function (Blueprint $table) {
            $table->dropColumn([
                'sub_total', 'fee', 'fee_type', 'fee_rate', 'grand_total',
                'fiat_currency_id', 'crypto_currency_id',
                'tr_request_id', 'dr_tr_id', 'cr_tr_id',
            ]);
        });
    }
};
