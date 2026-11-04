<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('risk_score_matrix', function (Blueprint $table) {
            $table->id();
            $table->string('matrix_type')->nullable();
            $table->string('title')->nullable();
            $table->decimal('know_your_customer', 5, 1)->nullable();
            $table->string('kyc_score')->nullable();
            $table->decimal('basel_index', 5, 2)->nullable();
            $table->string('basel_score')->nullable();
            $table->boolean('fatf_black_list')->default(false);
            $table->boolean('fatf_grey_list')->default(false);
            $table->decimal('transparency_corruption_index')->nullable();
            $table->string('transparency_score')->nullable();
            $table->decimal('global_terrorism_index', 5, 1)->nullable();
            $table->string('global_terrorism_score')->nullable();
            $table->boolean('eu_commission_targeted')->default(false);
            $table->boolean('eu_commission_targeted_territories')->default(false);
            $table->boolean('eu_commission_sanctions')->default(false);
            $table->boolean('eu_commission_member_state')->default(false);;
            $table->string('final_score')->nullable()->default('null');
            $table->decimal('final_score_value')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('risk_score_matrix');
    }
};
