<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_beneficiaries', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('account_id');
            $table->string('label', 100);
            $table->string('wallet_address', 255);
            $table->string('destination_tag', 100)->nullable();
            $table->unsignedInteger('currency_id');
            $table->unsignedInteger('network_id');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['account_id', 'wallet_address', 'network_id'], 'unique_beneficiary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_beneficiaries');
    }
};