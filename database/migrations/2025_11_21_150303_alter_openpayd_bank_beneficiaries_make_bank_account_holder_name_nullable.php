<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterOpenpaydBankBeneficiariesMakeBankAccountHolderNameNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('openpayd_bank_beneficiaries', function (Blueprint $table) {
            // make the baccount_holder_name column nullable
            $table->string('bank_account_holder_name')->nullable()->change();
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
            // make the bank_account_holder_name column not nullable
            $table->string('bank_account_holder_name')->nullable(false)->change();
        });
    }
}
