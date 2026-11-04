<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHideForClientToTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('currencies', function (Blueprint $table) {
            // Adding the hide_for_client column to the currencies table
            $table->boolean('hide_for_client')->default(0)->after('request_auto_approval');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('currencies', function (Blueprint $table) {
            // Dropping the hide_for_client column from the currencies table
            $table->dropColumn('hide_for_client');
        });
    }
}
