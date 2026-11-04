<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSocCodeToClientIndividualsEmployerDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_individuals_employer_details', function (Blueprint $table) {
            // Add soc_code column
            $table->string('soc_code')->nullable()->after('occupation_business');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_individuals_employer_details', function (Blueprint $table) {
            // Drop soc_code column
            $table->dropColumn('soc_code');
        });
    }
}
