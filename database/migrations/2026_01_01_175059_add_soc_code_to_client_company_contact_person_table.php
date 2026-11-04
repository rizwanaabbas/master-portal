<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSocCodeToClientCompanyContactPersonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_company_contact_person', function (Blueprint $table) {
            // add soc_code column
            $table->string('soc_code')->nullable()->after('residence_country_id');
            $table->string('naics_code')->nullable()->after('residence_country_id');
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
            // drop soc_code column
            $table->dropColumn('soc_code');
            $table->dropColumn('naics_code');
        });
    }
}
