<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_bank_beneficiaries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->char('linked_client_id', 36)->nullable();
            $table->char('account_holder_id', 36);
            $table->char('beneficiary_id', 36);
            $table->string('bank_beneficiary_id')->nullable();
            $table->char('external_id', 36)->nullable();
            $table->string('status', 50)->default('VALID');

            $table->string('bank_postal_code')->nullable();
            $table->string('bank_city')->nullable();
            $table->string('bank_state')->nullable();
            $table->string('bank_account_currency')->nullable();

            $table->string('friendly_name')->nullable();
            $table->string('beneficiary_first_name')->nullable();
            $table->string('beneficiary_last_name')->nullable();
            $table->string('beneficiary_type', 50);

            $table->string('beneficiary_country', 2)->nullable();
            $table->string('beneficiary_state')->nullable();
            $table->string('beneficiary_postal_code')->nullable();
            $table->string('beneficiary_city')->nullable();
            $table->string('beneficiary_address_line')->nullable();

            $table->string('account_number')->nullable();
            $table->string('iban', 34);
            $table->string('bic', 11);
            $table->string('currency', 3);

            $table->string('bank_name')->nullable();
            $table->string('bank_address')->nullable();
            $table->string('bank_account_country', 2);
            $table->string('bank_account_holder_name')->nullable();

            $table->string('company_name')->nullable();
            $table->string('bank_account_alias')->nullable();
            $table->char('document_id', 36)->nullable();

            $table->string('intermediary_bank_account_country', 2)->nullable();
            $table->string('intermediary_name')->nullable();
            $table->string('intermediary_town_name')->nullable();
            $table->string('intermediary_postal_code')->nullable();

            $table->string('bban')->nullable();
            $table->json('payment_types')->nullable();
            $table->json('bank_routing_codes')->nullable();

            $table->string('class', 50)->default('BANK');
            $table->string('tax_id')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('title')->nullable();

            $table->date('beneficiary_birth_date')->nullable();
            $table->string('bank_account_type')->nullable();

            $table->boolean('uploaded_kyc_document')->default(false);
            $table->json('metadata')->nullable();
            $table->char('parent_beneficiary_id', 36)->nullable();

            $table->timestamps();
        });

        // ✅ Transfer data
        DB::statement("
            INSERT INTO virtual_bank_beneficiaries
            SELECT * FROM openpayd_bank_beneficiaries
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_bank_beneficiaries');
    }
};
