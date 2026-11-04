<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableClientBeneficiariesAddColumnPaymentTypeId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_beneficiaries', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_type_id')->nullable()->after('bank_account_country');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_beneficiaries', function (Blueprint $table) {
            $table->dropColumn('payment_type_id');
        });
    }
}
