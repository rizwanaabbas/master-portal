<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FingateWallet extends Model
{
    use HasFactory;

    protected $table = 'fingate_wallets';

    protected $fillable = [
        'customer_id',
        'client_id',
        'currency_code',
        'wallet_name',
        'wallet_id',
    ];
}
