<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResidenceCountryIdToClientCompanyContactPersonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_company_contact_person', function (Blueprint $table) {
            if (!Schema::hasColumn('client_company_contact_person', 'residence_country_id')) {
                $table->integer('residence_country_id')->nullable()->after('nationality_id');
            }
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
            if (Schema::hasColumn('client_company_contact_person', 'residence_country_id')) {
                $table->dropColumn('residence_country_id');
            }
        });
    }
}
