<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('fee_transaction_charges', function (Blueprint $table) {
            // Direct references back to the originating B2c2 order and TR request.
            $table->unsignedBigInteger('b2c2_order_id')->nullable()->after('transaction_type');
            $table->unsignedBigInteger('tr_request_id')->nullable()->after('b2c2_order_id');

            $table->index('b2c2_order_id');
            $table->index('tr_request_id');
        });
    }

    public function down()
    {
        Schema::table('fee_transaction_charges', function (Blueprint $table) {
            $table->dropIndex(['b2c2_order_id']);
            $table->dropIndex(['tr_request_id']);
            $table->dropColumn(['b2c2_order_id', 'tr_request_id']);
        });
    }
};
