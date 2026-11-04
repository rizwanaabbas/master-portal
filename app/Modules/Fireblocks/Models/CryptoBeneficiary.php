<?php

namespace App\Modules\Fireblocks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Account;
use App\Models\Currency;
use App\Models\User;
use App\Modules\Fireblocks\Models\Network;
use App\Modules\Fireblocks\Models\CryptoExternalWallet;
use App\Modules\Chainalysis\Models\CryptoAddressCompliance;

class CryptoBeneficiary extends Model
{
    use SoftDeletes;

    protected $table = 'crypto_beneficiaries';

    protected $fillable = [
        'external_wallet_id',
        'fireblocks_wallet_id',
        'account_id',
        'label',
        'wallet_address',
        'destination_tag',
        'currency_id',
        'network_id',
        'is_active',
        'provider',
        'asset_type',
        'additional_info',
        'created_by',
        'fireblocks_asset_id',
        'fireblocks_asset_status',
        'fireblocks_activation_time',
        // Travel Rule PII
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
    ];

    protected $casts = [
        'is_active'                  => 'boolean',
        'fireblocks_activation_time' => 'datetime',
        'additional_info'            => 'array',
        'is_not_on_vasp_list'        => 'boolean',
        'is_ownership_declaration'   => 'boolean',
        'is_sending_to_myself'       => 'boolean',
        'dob'                        => 'date',
    ];

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Stable identifier used as the crypto_transactions_log.transaction_id for this
     * beneficiary. Shared by the create flow and the Fireblocks webhook so every
     * trail entry for the same whitelisted address is grouped together.
     */
    public function logKey(): string
    {
        return $this->fireblocks_wallet_id . ':' . $this->fireblocks_asset_id;
    }

    // ----------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function network()
    {
        return $this->belongsTo(Network::class, 'network_id');
    }

    public function externalWallet()
    {
        return $this->belongsTo(CryptoExternalWallet::class, 'external_wallet_id');
    }

    // public function compliance()
    // {
    //     return $this->hasOne(CryptoAddressCompliance::class, 'crypto_beneficiary_id');
    // }
    public function compliance()
    {
        return $this->hasOne(CryptoAddressCompliance::class, 'crypto_beneficiary_id')
            ->select([
                'id',
                'crypto_beneficiary_id', // Required for the relationship to work
                'risk',
                'risk_reason',
                'cluster_name',
                'status',
                'risk',
            ]);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ----------------------------------------------------------------
    // Scopes
    // ----------------------------------------------------------------

    /**
     * Only return active (non-deactivated) beneficiaries.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter by account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }
}