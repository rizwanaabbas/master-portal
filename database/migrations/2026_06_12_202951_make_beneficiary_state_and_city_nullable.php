<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeBeneficiaryStateAndCityNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_beneficiaries', function (Blueprint $table) {
            $table->string('beneficiary_state')->nullable()->change();
            $table->string('beneficiary_city')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_beneficiaries', function (Blueprint $table) {
            $table->string('beneficiary_state')->nullable(false)->change();
            $table->string('beneficiary_city')->nullable(false)->change();
        });
    }
}
