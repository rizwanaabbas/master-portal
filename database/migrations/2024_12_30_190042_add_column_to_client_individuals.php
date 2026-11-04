<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToClientIndividuals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_individuals', function (Blueprint $table) {
            //add city column
            $table->string('city')->nullable()->after('mailing_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_individuals', function (Blueprint $table) {
            // remove city column
            $table->dropColumn('city');
        });
    }
}
