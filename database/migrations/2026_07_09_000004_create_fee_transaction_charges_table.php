<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('fee_transaction_charges', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_reference')->index();
            $table->string('transaction_type');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('currency_id');
            $table->enum('direction', ['debit', 'credit']);
            $table->unsignedBigInteger('fee_tier_group_id')->nullable();
            $table->unsignedBigInteger('currency_fee_tier_id')->nullable();
            $table->string('fee_label');
            $table->enum('fee_type', ['percentage', 'flat']);
            $table->decimal('fee_rate', 18, 8);
            $table->decimal('transaction_amount', 24, 8);
            $table->decimal('fee_amount', 24, 8);
            $table->enum('crypto_fee_display', ['separate', 'summed'])->nullable();
            $table->enum('status', ['charged', 'reversed', 'waived'])->default('charged');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'account_id']);
            $table->index(['transaction_type', 'transaction_reference'], 'ftc_type_reference_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('fee_transaction_charges');
    }
};
