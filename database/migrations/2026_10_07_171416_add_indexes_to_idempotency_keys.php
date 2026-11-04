<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToIdempotencyKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {

        Schema::table('account_transaction_requests', function (Blueprint $bluePrint) {
            $bluePrint->index('idempotency_key', 'account_transaction_requests_idempotency_key_index');
        });

        Schema::table('clients', function (Blueprint $bluePrint) {
            $bluePrint->index('idempotency_key', 'clients_idempotency_key_index');
        });

        Schema::table('client_beneficiaries', function (Blueprint $bluePrint) {
            $bluePrint->index('idempotency_key', 'client_beneficiaries_idempotency_key_index');
        });

        Schema::table('client_company_files', function (Blueprint $bluePrint) {
            $bluePrint->index('idempotency_key', 'client_company_files_idempotency_key_index');
        });

        Schema::table('client_individual_files', function (Blueprint $bluePrint) {
            $bluePrint->index('idempotency_key', 'client_individual_files_idempotency_key_index');
        });

        Schema::table('client_kyc_records', function (Blueprint $bluePrint) {
            $bluePrint->index('idempotency_key', 'client_kyc_records_idempotency_key_index');
        });

        Schema::table('mobile_requests', function (Blueprint $bluePrint) {
            $bluePrint->index('idempotency_key', 'mobile_requests_idempotency_key_index');
        });

        Schema::table('idempotency_keys', function (Blueprint $bluePrint) {
            $bluePrint->index('idempotency_key', 'idempotency_keys_idempotency_key_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Drop index from account_transaction_requests
        Schema::table('account_transaction_requests', function (Blueprint $bluePrint) {
            $bluePrint->dropIndex('account_transaction_requests_idempotency_key_index');
        });

        // Drop index from clients
        Schema::table('clients', function (Blueprint $bluePrint) {
            $bluePrint->dropIndex('clients_idempotency_key_index');
        });

        // Drop index from client_beneficiaries
        Schema::table('client_beneficiaries', function (Blueprint $bluePrint) {
            $bluePrint->dropIndex('client_beneficiaries_idempotency_key_index');
        });

        // Drop index from client_company_files
        Schema::table('client_company_files', function (Blueprint $bluePrint) {
            $bluePrint->dropIndex('client_company_files_idempotency_key_index');
        });

        // Drop index from client_individual_files
        Schema::table('client_individual_files', function (Blueprint $bluePrint) {
            $bluePrint->dropIndex('client_individual_files_idempotency_key_index');
        });

        // Drop index from client_kyc_records
        Schema::table('client_kyc_records', function (Blueprint $bluePrint) {
            $bluePrint->dropIndex('client_kyc_records_idempotency_key_index');
        });

        // Drop index from mobile_requests
        Schema::table('mobile_requests', function (Blueprint $bluePrint) {
            $bluePrint->dropIndex('mobile_requests_idempotency_key_index');
        });

        // Drop index from idempotency_keys
        Schema::table('idempotency_keys', function (Blueprint $bluePrint) {
            $bluePrint->dropIndex('idempotency_keys_idempotency_key_index');
        });
    }
}
