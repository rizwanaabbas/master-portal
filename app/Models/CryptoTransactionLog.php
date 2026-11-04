<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CryptoTransactionLog extends Model
{
    protected $table = 'crypto_transactions_log'; // exact table name

    protected $primaryKey = 'id'; // change if different

    public $timestamps = true; // set false if no created_at/updated_at

    protected $fillable = [
        'transaction_id',
        'details',
        'crypto_system',
        'source',
    ];
    protected $casts = [
        'details' => 'array',
    ];
    // protected $casts = [
    //     'payload' => 'array', // if JSON column
    //     'amount' => 'decimal:8',
    // ];
}