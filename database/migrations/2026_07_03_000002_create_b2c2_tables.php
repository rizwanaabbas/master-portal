<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('b2c2_balances', function (Blueprint $table) {
            $table->id();
            $table->string('currency');
            $table->decimal('amount', 20, 8)->default(0);
            $table->decimal('hold', 20, 8)->default(0);
            $table->decimal('available', 20, 8)->default(0);
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });

        Schema::create('b2c2_instruments', function (Blueprint $table) {
            $table->id();
            $table->string('instrument')->unique();
            $table->string('name')->nullable();
            $table->string('instrument_type')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });

        Schema::create('b2c2_rfqs', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_id')->unique()->nullable();
            $table->string('client_rfq_id');
            $table->string('instrument');
            $table->string('side');
            $table->decimal('quantity', 20, 8);
            $table->decimal('price', 20, 8)->nullable();
            $table->string('status')->default('pending');
            $table->dateTime('valid_until')->nullable();
            $table->dateTime('created')->nullable();
            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });

        Schema::create('b2c2_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique()->nullable();
            $table->string('client_order_id')->nullable();
            $table->string('instrument');
            $table->string('side');
            $table->decimal('quantity', 20, 8);
            $table->decimal('price', 20, 8)->nullable();
            $table->decimal('filled_amount', 20, 8)->nullable();
            $table->decimal('remaining_amount', 20, 8)->nullable();
            $table->string('order_type')->default('FOK');
            $table->string('status')->nullable();
            $table->string('error_message')->nullable();
            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });

        Schema::create('b2c2_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->string('method');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->integer('response_status')->nullable();
            $table->string('status')->default('success');
            $table->string('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('b2c2_transaction_logs');
        Schema::dropIfExists('b2c2_orders');
        Schema::dropIfExists('b2c2_rfqs');
        Schema::dropIfExists('b2c2_instruments');
        Schema::dropIfExists('b2c2_balances');
    }
};
