<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crypto_beneficiaries', function (Blueprint $table) {
            // Travel Rule PII — captured once per beneficiary
            $table->string('beneficiary_type')->nullable()->after('label'); // INDIVIDUAL | BUSINESS
            $table->string('beneficiary_first_name')->nullable()->after('beneficiary_type');
            $table->string('beneficiary_last_name')->nullable()->after('beneficiary_first_name');
            $table->string('beneficiary_legal_name')->nullable()->after('beneficiary_last_name');
            $table->date('dob')->nullable()->after('beneficiary_legal_name');
            $table->string('country_code', 2)->nullable()->after('dob');

            $table->string('wallet_type')->nullable()->after('country_code'); // CUSTODIAL | SELF_HOSTED
            $table->string('beneficiary_vasp_did')->nullable()->after('wallet_type');
            $table->string('beneficiary_vasp_name')->nullable()->after('beneficiary_vasp_did');
            $table->boolean('is_not_on_vasp_list')->default(false)->after('beneficiary_vasp_name');
            $table->boolean('is_ownership_declaration')->default(false)->after('is_not_on_vasp_list');
            $table->boolean('is_sending_to_myself')->default(false)->after('is_ownership_declaration');
        });
    }

    public function down(): void
    {
        Schema::table('crypto_beneficiaries', function (Blueprint $table) {
            $table->dropColumn([
                'beneficiary_type',
                'beneficiary_first_name',
                'beneficiary_last_name',
                'beneficiary_legal_name',
                'dob',
                'country_code',
                'wallet_type',
                'beneficiary_vasp_did',
                'beneficiary_vasp_name',
                'is_not_on_vasp_list',
                'is_ownership_declaration',
                'is_sending_to_myself',
            ]);
        });
    }
};
