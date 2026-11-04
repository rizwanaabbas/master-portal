<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenpaydBankBeneficiary extends Model
{
    use HasFactory;

    protected $table = 'openpayd_bank_beneficiaries';

    protected $fillable = [
        'user_id',
        'linked_client_id',
        'account_holder_id',
        'beneficiary_id',
        'bank_beneficiary_id',
        'external_id',
        'status',
        'friendly_name',
        'beneficiary_first_name',
        'beneficiary_last_name',
        'beneficiary_type',
        'beneficiary_country',
        'beneficiary_state',
        'beneficiary_postal_code',
        'beneficiary_city',
        'beneficiary_address_line',
        'account_number',
        'iban',
        'bic',
        'currency',
        'bank_name',
        'bank_address',
        'bank_account_country',
        'bank_account_holder_name',
        'company_name',
        'bank_account_alias',
        'document_id',
        'intermediary_bank_account_country',
        'intermediary_name',
        'intermediary_town_name',
        'intermediary_postal_code',
        'bban',
        'payment_types',
        'bank_routing_codes',
        'class',
        'tax_id',
        'phone_number',
        'title',
        'beneficiary_birth_date',
        'bank_account_type',
        'uploaded_kyc_document',
        'parent_beneficiary_id',
        'metadata',
    ];

    protected $casts = [
        'payment_types' => 'array',
        'bank_routing_codes' => 'array',
        'metadata' => 'array',
    ];
}

