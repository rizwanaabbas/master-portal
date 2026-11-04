<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_transaction_requests', function (Blueprint $table) {
            $table->char('idempotency_key', 36)->after('id')->unique()->nullable();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->char('idempotency_key', 36)->after('id')->unique()->nullable();
        });

        Schema::table('client_beneficiaries', function (Blueprint $table) {
            $table->char('idempotency_key', 36)->after('id')->unique()->nullable();
        });

        Schema::table('client_company_files', function (Blueprint $table) {
            $table->char('idempotency_key', 36)->after('id')->unique()->nullable();
        });

        Schema::table('client_individual_files', function (Blueprint $table) {
            $table->char('idempotency_key', 36)->after('id')->unique()->nullable();
        });

        Schema::table('client_kyc_records', function (Blueprint $table) {
            $table->char('idempotency_key', 36)->after('id')->unique()->nullable();
        });

        Schema::table('mobile_requests', function (Blueprint $table) {
            $table->char('idempotency_key', 36)->after('id')->unique()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
