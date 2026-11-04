<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLicenseNumberToOwnCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return voidown-company/editown-company/editown-company/edit
     */
    public function up()
    {
        Schema::table('own_companies', function (Blueprint $table) {
            $table->string('license_number')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('own_companies', function (Blueprint $table) {
            $table->dropColumn('license_number');
        });
    }
}
