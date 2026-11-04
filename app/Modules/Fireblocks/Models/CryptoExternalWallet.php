<?php

namespace App\Modules\Fireblocks\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
class CryptoExternalWallet extends Model
{
    protected $table = 'crypto_external_wallets';

    protected $fillable = [
        'client_id',
        'fireblocks_wallet_id',
        'name',
        'customer_ref_id',
        'created_by',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function beneficiaries()
    {
        return $this->hasMany(CryptoBeneficiary::class, 'external_wallet_id');
    }
}
