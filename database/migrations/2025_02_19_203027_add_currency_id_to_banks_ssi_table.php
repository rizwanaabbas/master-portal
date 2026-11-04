<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrencyIdToBanksSsiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banks_ssi', function (Blueprint $table) {
            $table->integer('currency_id')->nullable()->after('bank_id');
            $table->integer('updated_by')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banks_ssi', function (Blueprint $table) {
            $table->dropColumn('currency_id');
            $table->dropColumn('updated_by');
        });
    }
}
