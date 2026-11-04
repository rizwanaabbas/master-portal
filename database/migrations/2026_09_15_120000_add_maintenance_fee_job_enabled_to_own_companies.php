<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaintenanceFeeJobEnabledToOwnCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * Switch (System Settings → Own Company) that gates the monthly
     * `deduct:maintenance-fee` schedule. Defaults to ON so existing
     * installations keep deducting exactly as before this column existed.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('own_companies', function (Blueprint $table) {
            $table->boolean('maintenance_fee_job_enabled')->default(true)->after('show_introducer_field');
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
            $table->dropColumn('maintenance_fee_job_enabled');
        });
    }
}
