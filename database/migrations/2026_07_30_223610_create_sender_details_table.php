<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSenderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sender_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tr_request_id');
            $table->string('sender_name')->nullable();
            $table->string('sender_address')->nullable();
            $table->string('sender_city')->nullable();
            $table->unsignedBigInteger('sender_country')->nullable();
            $table->string('sending_bank_name')->nullable();
            $table->string('sending_bank_address')->nullable();
            $table->string('sending_bank_city')->nullable();
            $table->unsignedBigInteger('sending_bank_country')->nullable();
            $table->string('sending_bank_swift')->nullable();
            $table->string('intermediary_bank_name')->nullable();
            $table->string('intermediary_bank_swift')->nullable();
            $table->timestamps();

            $table->foreign('tr_request_id')
                ->references('id')
                ->on('account_transaction_requests')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sender_details');
    }
}
