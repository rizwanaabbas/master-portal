<?php

namespace App\Modules\Chainalysis\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Fireblocks\Models\CryptoBeneficiary;

class CryptoAddressCompliance extends Model
{
    protected $table = 'crypto_address_compliance';

    protected $fillable = [
        'crypto_beneficiary_id',
        'wallet_address',
        'entity_type',
        'risk',
        'status',
        'address_type',
        'cluster_name',
        'cluster_category',
        'risk_reason',
        'provider',
        'complete_info',
    ];

    protected $casts = [
        'complete_info' => 'array',
    ];

    public function beneficiary()
    {
        return $this->belongsTo(CryptoBeneficiary::class, 'crypto_beneficiary_id');
    }
}
