<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableBeneficiaryRequiredFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('beneficiary_required_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('currency_id');
            $table->unsignedBigInteger('country_id');
            $table->boolean("is_active")->default(1);
            $table->string('beneficiary_type');
            $table->json('required_fields')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('beneficiary_required_fields');
    }
}
