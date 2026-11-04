<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientPortalDarkLogoToOwnCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('own_companies', function (Blueprint $table) {
            $table->text('client_portal_dark_logo')->nullable()->after('client_portal_light_logo');
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
            $table->dropColumn('client_portal_dark_logo');
        });
    }
}
