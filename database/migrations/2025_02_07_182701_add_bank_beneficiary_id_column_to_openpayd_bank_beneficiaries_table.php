<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBankBeneficiaryIdColumnToOpenpaydBankBeneficiariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('openpayd_bank_beneficiaries', function (Blueprint $table) {
            // add bank_beneficiary_id column to openpayd_bank_beneficiaries table
            $table->string('bank_beneficiary_id')->nullable()->after('beneficiary_id');
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
            // drop bank_beneficiary_id column from openpayd_bank_beneficiaries table
            $table->dropColumn('bank_beneficiary_id');
        });
    }
}
