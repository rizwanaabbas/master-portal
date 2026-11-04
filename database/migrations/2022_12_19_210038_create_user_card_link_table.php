<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserCardLinkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_card_links', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id')->unique();
            $table->integer('detail_id')->unique();
            $table->string('username');
            $table->boolean('status')->nullable();
            $table->integer('created_by');
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
        Schema::dropIfExists('user_card_links');
    }
}
