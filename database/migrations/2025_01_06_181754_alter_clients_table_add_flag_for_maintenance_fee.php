<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterClientsTableAddFlagForMaintenanceFee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('charge_maintenance_fee')->after('create_user')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Drop columns if they exist
            if (Schema::hasColumn('clients', 'charge_maintenance_fee')) {
                $table->dropColumn('charge_maintenance_fee');
            }
        });
    }
}
