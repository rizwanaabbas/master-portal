<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBusinessStreet2AddressColumnToClientCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_companies', function (Blueprint $table) {
            $table->string('business_street_2_address')->nullable()->after('business_reg_street_address');
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
            $table->dropColumn('business_street_2_address');
        });
    }
}
