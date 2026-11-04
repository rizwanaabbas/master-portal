<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentBeneficiaryIdColumnToOpenpaydBankBeneficiariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('openpayd_bank_beneficiaries', function (Blueprint $table) {
            $table->uuid('parent_beneficiary_id')->nullable()->after('metadata');
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
            $table->dropColumn('parent_beneficiary_id');
        });
    }
}
