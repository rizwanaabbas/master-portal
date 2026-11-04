<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDeletedToAccountTransactionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_transaction_requests', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(false)->after('remarks');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_transaction_requests', function (Blueprint $table) {
            $table->dropColumn('is_deleted');
        });
    }
}
