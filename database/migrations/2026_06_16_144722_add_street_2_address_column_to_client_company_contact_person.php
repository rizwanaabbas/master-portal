<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStreet2AddressColumnToClientCompanyContactPerson extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_company_contact_person', function (Blueprint $table) {
            $table->string('street_2_address')->nullable()->after('street_address');
            $table->string('contact_city')->nullable()->after('street_2_address');
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
            $table->dropColumn('street_2_address');
            $table->dropColumn('contact_city');
        });
    }
}
