<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFireblocksVaultAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fireblocks_vault_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->string('vault_title');
            $table->string('vault_id')->index();
            $table->timestamps();

            $table->unique(['account_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fireblocks_vault_accounts');
    }
}
