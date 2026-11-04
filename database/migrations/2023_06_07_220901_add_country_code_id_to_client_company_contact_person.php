<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountryCodeIdToClientCompanyContactPerson extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_company_contact_person', function (Blueprint $table) {
            $table->integer('country_code_id')->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_company_contact_person', function (Blueprint $table) {
            $table->dropColumn('country_code_id');
        });
    }
}
