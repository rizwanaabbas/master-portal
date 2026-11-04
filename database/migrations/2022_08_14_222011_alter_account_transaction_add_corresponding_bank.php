<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAccountTransactionAddCorrespondingBank extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_transactions', function (Blueprint $table) {

            $table->string("corresponding_bank_name")->nullable()->after("ex_rate");
            $table->string("corresponding_bank_swift_code")->nullable()->after("corresponding_bank_name");
            $table->text("corresponding_bank_address")->nullable()->after("corresponding_bank_swift_code");

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
