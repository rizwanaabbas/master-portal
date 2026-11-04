<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableOpenpaydBankBeneficiaryAddAllOpenpaydBeneficiaryFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('openpayd_bank_beneficiaries', function (Blueprint $table) {
            // Define all expected fields
            $fields = [
                'payment_types',
                'beneficiary_type',
                'bank_routing_codes',
                'bank_account_currency',
                'beneficiary_country',
                'bank_account_country',
                'bank_address',
                'bank_state',
                'bank_city',
                'bank_postal_code',
                'company_name',
                'beneficiary_first_name',
                'beneficiary_last_name',
                'bank_account_type',
                'bank_name',
                'bank_account_holder_name',
                'account_number',
                'iban',
                'bic',
                'beneficiary_birth_date',
                'beneficiary_address_line',
                'beneficiary_state',
                'beneficiary_city',
                'beneficiary_postal_code',
                'phone_number',
                'friendly_name',
                'tax_id',
                'intermediary_town_name',
                'intermediary_postal_code',
                'intermediary_name',
                'intermediary_bank_account_country',
            ];

            // Dynamically add missing columns
            foreach ($fields as $column) {
                if (!Schema::hasColumn('openpayd_bank_beneficiaries', $column)) {
                    $table->string($column)->nullable()->after('status');
                }
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
        Schema::table('openpayd_bank_beneficiaries', function (Blueprint $table) {
            $fields = [
                'payment_types',
                'beneficiary_type',
                'bank_routing_codes',
                'bank_account_currency',
                'beneficiary_country',
                'bank_account_country',
                'bank_address',
                'bank_state',
                'bank_city',
                'bank_postal_code',
                'company_name',
                'beneficiary_first_name',
                'beneficiary_last_name',
                'bank_account_type',
                'bank_name',
                'bank_account_holder_name',
                'account_number',
                'iban',
                'bic',
                'beneficiary_birth_date',
                'beneficiary_address_line',
                'beneficiary_state',
                'beneficiary_city',
                'beneficiary_postal_code',
                'phone_number',
                'friendly_name',
                'tax_id',
                'intermediary_town_name',
                'intermediary_postal_code',
                'intermediary_name',
                'intermediary_bank_account_country',
            ];

            foreach ($fields as $column) {
                if (Schema::hasColumn('openpayd_bank_beneficiaries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
