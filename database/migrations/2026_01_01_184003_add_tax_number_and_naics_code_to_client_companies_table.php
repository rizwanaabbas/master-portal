<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaxNumberAndNaicsCodeToClientCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_companies', function (Blueprint $table) {
            // add tax_number column
            $table->string('tax_number')->nullable()->after('business_reg_number');
            $table->string('naics_code')->nullable()->after('business_reg_number');
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
            // drop tax_number column
            $table->dropColumn('tax_number');
            $table->dropColumn('naics_code');
        });
    }
}
