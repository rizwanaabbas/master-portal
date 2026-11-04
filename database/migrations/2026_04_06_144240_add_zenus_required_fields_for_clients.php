<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZenusRequiredFieldsForClients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {   
        // Zenus Individual Client Fields
        Schema::table('client_individuals', function (Blueprint $table) {
            $table->string('zip_code')->after('mailing_address')->nullable();
            $table->string('state_region')->after('mailing_address')->nullable();
            $table->string('street_address')->after('mailing_address')->nullable();
        });
        
        // Zenus Company Client Fields
        Schema::table('client_company_contact_person', function (Blueprint $table) {
            $table->string('zip_code')->after('designation')->nullable();
            $table->string('state_region')->after('designation')->nullable();
            $table->string('street_address')->after('designation')->nullable();
            $table->string('address')->after('designation')->nullable();
        });
        
        Schema::table('client_companies', function (Blueprint $table) {
            $table->string('zip_code')->after('company_city')->nullable();
            $table->string('company_region')->after('company_city')->nullable();
            $table->string('business_reg_street_address')->after('business_reg_address')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Zenus Individual Client Fields
        Schema::table('client_individuals', function (Blueprint $table) {
            $table->dropColumn(['zip_code', 'state_region', 'street_address']);
        });

        // Zenus Company Contact Person Fields
        Schema::table('client_company_contact_person', function (Blueprint $table) {
            $table->dropColumn(['zip_code', 'state_region', 'street_address', 'address']);
        });

        // Zenus Company Fields
        Schema::table('client_companies', function (Blueprint $table) {
            $table->dropColumn(['zip_code', 'company_region', 'business_reg_street_address']);
        });
    }
}
