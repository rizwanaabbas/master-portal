<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAccountTransactionsAddExRate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->double('ex_rate')->after('amount')->nullable();
            $table->integer("link_tr_id")->after("account_id")->nullable();
            $table->integer("tr_request_id")->after("link_tr_id")->nullable();
            $table->integer("beneficiary_id")->after("tr_request_id")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
