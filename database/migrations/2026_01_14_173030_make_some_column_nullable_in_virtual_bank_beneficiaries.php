<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeSomeColumnNullableInVirtualBankBeneficiaries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('virtual_bank_beneficiaries', function (Blueprint $table) {
            // Make account_holder_id, beneficiary_id, status, iban, bic, class, uploaded_kyc_document nullable
            $table->string('account_holder_id')->nullable()->change();
            $table->string('beneficiary_id')->nullable()->change();
            $table->string('status')->nullable()->change();
            $table->string('iban')->nullable()->change();
            $table->string('bic')->nullable()->change();
            $table->string('class')->nullable()->change();
            $table->integer('uploaded_kyc_document')->nullable()->change();
            // $table->string('parent_beneficiary_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('virtual_bank_beneficiaries', function (Blueprint $table) {
            // Revert account_holder_id, beneficiary_id, status, iban, bic, class, uploaded_kyc_document to not nullable
            $table->string('account_holder_id')->nullable(false)->change();
            $table->string('beneficiary_id')->nullable(false)->change();
            $table->string('status')->nullable(false)->change();
            $table->string('iban')->nullable(false)->change();
            $table->string('bic')->nullable(false)->change();
            $table->string('class')->nullable(false)->change();
            $table->integer('uploaded_kyc_document')->nullable(false)->change();
            // $table->string('parent_beneficiary_id')->nullable(true)->change();
        });
    }
}
