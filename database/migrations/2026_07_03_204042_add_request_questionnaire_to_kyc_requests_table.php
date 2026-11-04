<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestQuestionnaireToKycRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kyc_requests', function (Blueprint $table) {
            $table->boolean('request_questionnaire')->default(false)->after('request_signature');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kyc_requests', function (Blueprint $table) {
            $table->dropColumn('request_questionnaire');
        });
    }
}
