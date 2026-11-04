<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableMaintenanceFeeReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('maintenance_fee_reports', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('unique_id')->unique();
            $table->integer('client_id');
            $table->integer('account_id')->nullable();
            $table->integer('amount')->nullable();
            $table->boolean('have_paid');
            $table->smallInteger('previous_pending_months');
            $table->datetime("date_charged");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('maintenance_fee_reports');
    }
}
