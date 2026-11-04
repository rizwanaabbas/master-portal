<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueFireblocksTransactionId extends Migration
{
    public function up()
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            $table->unique('fireblocks_tx_id', 'fireblocks_transactions_tx_id_unique');
        });
    }

    public function down()
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            $table->dropUnique('fireblocks_transactions_tx_id_unique');
        });
    }
}
