<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('client_fee_tier_group_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('fee_tier_group_id');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'effective_to']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('client_fee_tier_group_history');
    }
};
