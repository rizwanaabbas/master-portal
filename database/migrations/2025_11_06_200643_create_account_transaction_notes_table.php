<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountTransactionNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_transaction_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_request_id')->nullable();
            $table->text('note');
            $table->boolean('is_deleted')->default(false);
            $table->foreign('transaction_request_id')->references('id')->on('account_transaction_requests')->onDelete('cascade');
            $table->integer('created_by')->nullable();
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
        Schema::dropIfExists('account_transaction_notes');
    }
}
