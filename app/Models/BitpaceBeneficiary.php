<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BitpaceBeneficiary extends Model
{
    use SoftDeletes;

    protected $table = 'bitpace_beneficiaries';

    protected $fillable = [
        'tr_request_id',
        'client_id',

        // Beneficiary Type
        'beneficiary_type',

        // INDIVIDUAL
        'beneficiary_name',
        'beneficiary_last_name',
        'dob',

        // BUSINESS
        'beneficiary_legal_name',
        'registration_number',
        'lei_code',

        // Common
        'country_code',

        // Wallet Type
        'wallet_type',

        // Custodial
        'beneficiary_vasp_did',
        'beneficiary_vasp_name',
        'is_not_on_vasp_list',

        // Self Hosted
        'is_ownership_declaration',

        // Sending to myself
        'is_sending_to_myself',
    ];

    protected $casts = [
        'dob' => 'date',
        'is_not_on_vasp_list' => 'boolean',
        'is_ownership_declaration' => 'boolean',
        'is_sending_to_myself' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function transactionRequest()
    {
        return $this->belongsTo(AccountTransactionRequest::class, 'tr_request_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isIndividual()
    {
        return $this->beneficiary_type === 'INDIVIDUAL';
    }

    public function isBusiness()
    {
        return $this->beneficiary_type === 'BUSINESS';
    }

    public function isCustodialWallet()
    {
        return $this->wallet_type === 'CUSTODIAL';
    }

    public function isSelfHostedWallet()
    {
        return $this->wallet_type === 'SELF_HOSTED';
    }
}