<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSumsubApplicantIdToClientKycRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_kyc_records', function (Blueprint $table) {
            $table->string('sumsub_applicant_id')->nullable()->after('sumsub_kyc_reject_reason');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_kyc_records', function (Blueprint $table) {
            $table->dropColumn('sumsub_applicant_id');
        });
    }
}
