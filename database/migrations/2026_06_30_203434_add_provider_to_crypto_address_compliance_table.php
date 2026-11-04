<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProviderToCryptoAddressComplianceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crypto_address_compliance', function (Blueprint $table) {
            $table->string('provider')->default('chainalysis')->after('risk_reason');
        });
    }

    public function down()
    {
        Schema::table('crypto_address_compliance', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
}
