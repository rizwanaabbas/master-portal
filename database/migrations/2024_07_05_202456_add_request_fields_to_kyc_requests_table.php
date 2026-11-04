<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestFieldsToKycRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kyc_requests', function (Blueprint $table) {
            $table->boolean('request_passport')->default(0)->nullable();
            $table->boolean('request_selfie')->default(0)->nullable();
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
            $table->dropColumn('request_passport');
            $table->dropColumn('request_selfie');
        });
    }
}
