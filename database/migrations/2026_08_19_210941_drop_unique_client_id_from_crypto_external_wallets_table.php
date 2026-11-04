<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUniqueClientIdFromCryptoExternalWalletsTable extends Migration
{
    public function up(): void
    {
        Schema::table('crypto_external_wallets', function (Blueprint $table) {
            // 1. Drop foreign key first
            $table->dropForeign(['client_id']);

            // 2. Drop the unique index
            $table->dropUnique('crypto_external_wallets_client_id_unique');

            // 3. Re-add foreign key (creates a standard non-unique index)
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('crypto_external_wallets', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->unique('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }
}
