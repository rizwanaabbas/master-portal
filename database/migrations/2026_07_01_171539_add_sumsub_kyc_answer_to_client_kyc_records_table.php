<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSumsubKycAnswerToClientKycRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_kyc_records', function (Blueprint $table) {
            $table->string('sumsub_kyc_answer')->nullable()->after('sumsub_kyc_status');
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
            $table->dropColumn('sumsub_kyc_answer');
        });
    }
}
