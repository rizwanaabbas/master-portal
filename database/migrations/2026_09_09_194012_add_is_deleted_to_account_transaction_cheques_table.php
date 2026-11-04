<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDeletedToAccountTransactionChequesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_transaction_cheques', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(false)->after('cheque_bank');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_transaction_cheques', function (Blueprint $table) {
            $table->dropColumn('is_deleted');
        });
    }
}
