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
        Schema::table('client_beneficiaries', function (Blueprint $table) {
            $table->unsignedInteger('account_currency')->nullable()->after('swift_code');
            $table->unsignedInteger('beneficiary_country')->nullable()->after('account_currency');
            $table->unsignedInteger('bank_account_country')->nullable()->after('beneficiary_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_beneficiaries', function (Blueprint $table) {
            $table->dropColumn(['account_currency', 'beneficiary_country', 'bank_account_country']);
        });
    }
};

