<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientRiskRating extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_risk_rating', function (Blueprint $table) {
            $table->id();
            $table->integer("client_id");
            $table->text('scoring_data')->nullable();
            $table->integer('risk_score')->nullable();
            $table->text('remarks')->nullable(); 
            $table->integer("created_by")->nullable();
            $table->integer("updated_by")->nullable();
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
        Schema::dropIfExists('client_risk_rating');
    }
}
