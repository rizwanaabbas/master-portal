<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrnIdInOpenpaydTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('openpayd_transactions', function (Blueprint $table) {
            // add trn_id column
            $table->integer('trn_id')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('openpayd_transactions', function (Blueprint $table) {
            // drop trn_id column
            $table->dropColumn('trn_id');
        });
    }
}
