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
        Schema::create('bitpace_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tr_request_id')->comment('Reference to account_transaction_requests table');
            $table->unsignedBigInteger('client_id')->comment('Reference to clients table');
            
            // Beneficiary Type
            $table->enum('beneficiary_type', ['INDIVIDUAL', 'BUSINESS'])->default('INDIVIDUAL');
            
            // INDIVIDUAL fields
            $table->string('beneficiary_name')->nullable()->comment('Legal first name for INDIVIDUAL');
            $table->string('beneficiary_last_name')->nullable()->comment('Legal last name for INDIVIDUAL');
            $table->date('dob')->nullable()->comment('Date of birth for INDIVIDUAL (YYYY-MM-DD)');
            
            // BUSINESS fields
            $table->string('beneficiary_legal_name')->nullable()->comment('Full registered legal name for BUSINESS');
            $table->string('registration_number')->nullable()->comment('Business registration number for BUSINESS');
            $table->string('lei_code')->nullable()->comment('20-char LEI code for BUSINESS (ISO 17442)');
            
            // Common fields
            $table->string('country_code', 2)->nullable()->comment('ISO 3166-1 alpha-2 country code');
            
            // Wallet Type
            $table->enum('wallet_type', ['CUSTODIAL', 'SELF_HOSTED'])->default('CUSTODIAL');
            
            // CUSTODIAL fields
            $table->string('beneficiary_vasp_did')->nullable()->comment('VASP DID for CUSTODIAL wallets');
            $table->string('beneficiary_vasp_name')->nullable()->comment('VASP name fallback for CUSTODIAL');
            $table->boolean('is_not_on_vasp_list')->default(false)->comment('Flag if VASP is not on list');
            
            // SELF_HOSTED fields
            $table->boolean('is_ownership_declaration')->default(false)->comment('Required for SELF_HOSTED - ownership confirmation');
            
            // Sending to myself
            $table->boolean('is_sending_to_myself')->default(false)->comment('True if sender and beneficiary are same entity');
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes only (no foreign keys to avoid type mismatch issues)
            $table->index(['tr_request_id']);
            $table->index(['client_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bitpace_beneficiaries');
    }
};
