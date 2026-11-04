<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kyc_requests', function (Blueprint $table) {
            $table->string('OnBoardingUrl')->nullable()->after('request_selfie');
            $table->string('kyc_provider', 30)->nullable()->after('OnBoardingUrl');
            $table->text('kyc_id')->nullable()->after('kyc_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kyc_requests', function (Blueprint $table) {
            $table->dropColumn('OnBoardingUrl');
        });
    }
};
