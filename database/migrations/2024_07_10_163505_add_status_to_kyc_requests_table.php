<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToKycRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kyc_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('kyc_requests', 'status')) {
                $table->string('status')->nullable()->default('pending');
            }
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
            $table->dropColumn('status');
        });
    }
}
