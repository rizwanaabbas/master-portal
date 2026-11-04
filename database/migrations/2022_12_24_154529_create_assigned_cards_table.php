<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignedCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assigned_cards', function (Blueprint $table) {
            $table->id();
            $table->string('userName');
            $table->string('cardHolderName')->nullable();
            $table->string('cardName')->nullable();
            $table->string('cardNumber')->nullable();
            $table->boolean('physical')->nullable();
            $table->string('programType')->nullable();
            $table->string('mpin')->nullable();
            $table->string('status')->nullable();
            $table->string('card_created_at')->nullable();
            $table->string('cType')->nullable();
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
        Schema::dropIfExists('assigned_cards');
    }
}
