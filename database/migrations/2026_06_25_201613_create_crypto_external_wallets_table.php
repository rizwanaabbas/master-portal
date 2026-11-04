<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCryptoExternalWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crypto_external_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_id')->unique();
            $table->string('fireblocks_wallet_id')->unique();
            $table->string('name');
            $table->string('customer_ref_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crypto_external_wallets');
    }
}
