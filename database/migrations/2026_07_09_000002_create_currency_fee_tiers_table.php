<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('currency_fee_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_tier_group_id');
            // nullable = wildcard: applies to any currency not explicitly configured.
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->string('label');
            $table->enum('fee_type', ['percentage', 'flat']);
            $table->decimal('fee_value', 18, 8);
            $table->decimal('min_fee', 18, 8)->nullable();
            $table->decimal('max_fee', 18, 8)->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['fee_tier_group_id', 'currency_id', 'is_active'], 'cft_group_currency_active_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('currency_fee_tiers');
    }
};
