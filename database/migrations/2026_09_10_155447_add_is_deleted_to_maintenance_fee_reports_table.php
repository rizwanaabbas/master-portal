<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDeletedToMaintenanceFeeReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maintenance_fee_reports', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(false)->after('have_paid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maintenance_fee_reports', function (Blueprint $table) {
            $table->dropColumn('is_deleted');
        });
    }
}
