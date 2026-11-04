<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardStatementsDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('card_statements_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('statement_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->unsignedBigInteger('card_id')->nullable();
            $table->json('json_data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('card_statements_details');
    }
}

