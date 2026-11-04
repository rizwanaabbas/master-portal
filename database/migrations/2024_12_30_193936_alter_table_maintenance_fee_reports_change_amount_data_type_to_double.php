<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterTableMaintenanceFeeReportsChangeAmountDataTypeToDouble extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('maintenance_fee_reports', function (Blueprint $table) {
        //     $table->double('amount')->change();
        // });

        DB::statement('ALTER TABLE maintenance_fee_reports MODIFY amount DOUBLE');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maintenance_fee_reports', function (Blueprint $table) {
            //
        });
    }
}
