<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTablePerCurrencyMaintenanceFees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('per_currency_maintenance_fees', function (Blueprint $table) {
            $table->id();
            $table->integer('currency_id');
            $table->double('individual_fee');
            $table->double('company_fee');
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
        Schema::dropIfExists('per_currency_maintenance_fees');
    }
}
