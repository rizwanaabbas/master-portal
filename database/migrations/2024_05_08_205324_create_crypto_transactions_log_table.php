<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCryptoTransactionsLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crypto_transactions_log', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 100)->nullable();
            $table->text('details')->nullable();
            $table->string('crypto_system', 100)->nullable();
            $table->dateTime('date_created')->nullable();
            $table->string('source', 100)->nullable()->comment('weebhook, system');
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
        Schema::dropIfExists('crypto_transactions_log');
    }
}
