<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterRiskScoreToFloatInClientRiskRatings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_risk_rating', function (Blueprint $table) {
            $table->decimal('risk_score', 5, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_risk_rating', function (Blueprint $table) {
            $table->integer('risk_score')->change();
        });
    }
}
