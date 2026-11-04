<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToClientCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_companies', function (Blueprint $table) {
            //add company_country and city columns
            $table->string('company_country')->nullable()->after('company_place');
            $table->string('company_city')->nullable()->after('company_country');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_companies', function (Blueprint $table) {
            // remove company_country and city columns
            $table->dropColumn('company_country');
            $table->dropColumn('company_city');
        });
    }
}
