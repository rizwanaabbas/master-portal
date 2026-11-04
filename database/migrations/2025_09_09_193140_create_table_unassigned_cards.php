<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableUnassignedCards extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unassigned_cards', function (Blueprint $table) {
            $table->id();
            // Create a string column for card_code, which will be unique and indexed
            $table->string('card_code')->unique();
            $table->string('masked_card_number')->index();
            $table->string('card_type');
            $table->boolean('is_assigned')->default(false);
            $table->integer("assigned_to")->nullable()->comment('Client ID for clients table.');
            $table->string('provider');
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
        Schema::dropIfExists('unassigned_cards');
    }
}
