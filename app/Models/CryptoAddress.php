<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoAddress extends Model
{
    use HasFactory;

    protected $table = 'crypto_addresses';

    protected $fillable = [
        'account_id',
        'network',
        'destination_tag',
        'provider',
        'crypto_address',
        'ged_redemption_address',
        'legacy_address',
        'address_alias',
        'ged_external_id',
        'vault_account_id',
        'asset_id',
        'network_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
