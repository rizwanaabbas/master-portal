<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('b2c2_rfqs', function (Blueprint $table) {
            // The user who requested the quote — used to validate ownership at order time.
            $table->unsignedBigInteger('created_by')->nullable()->after('status');
        });

        Schema::table('b2c2_orders', function (Blueprint $table) {
            $table->string('rfq_id')->nullable()->after('client_order_id');
            $table->unsignedBigInteger('created_by')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('b2c2_rfqs', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });

        Schema::table('b2c2_orders', function (Blueprint $table) {
            $table->dropColumn(['rfq_id', 'created_by']);
        });
    }
};
