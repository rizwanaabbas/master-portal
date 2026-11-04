<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterOpenpaydBankBeneficiariesMakeBeneficiaryFirstLastNameNull extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('openpayd_bank_beneficiaries', function (Blueprint $table) {
            // make beneficiary_first_name and beneficiary_last_name nullable
            $table->string('beneficiary_first_name')->nullable()->change();
            $table->string('beneficiary_last_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('openpayd_bank_beneficiaries', function (Blueprint $table) {
            // make beneficiary_first_name and beneficiary_last_name not nullable
            $table->string('beneficiary_first_name')->nullable(false)->change();
            $table->string('beneficiary_last_name')->nullable(false)->change();
        });
    }
}
