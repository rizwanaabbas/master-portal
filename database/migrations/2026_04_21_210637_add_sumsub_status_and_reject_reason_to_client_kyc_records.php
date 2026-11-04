<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSumsubStatusAndRejectReasonToClientKycRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_kyc_records', function (Blueprint $table) {
            $table->string('sumsub_kyc_status')->nullable()->after('compliance_status');
            $table->text('sumsub_kyc_reject_reason')->nullable()->after('sumsub_kyc_status');
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
            $table->dropColumn('sumsub_kyc_status');
            $table->dropColumn('sumsub_kyc_reject_reason');
        });
    }
}
