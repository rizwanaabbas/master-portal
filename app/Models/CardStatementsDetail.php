<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardStatementsDetail extends Model
{
    protected $table = 'card_statements_details';

    protected $fillable = [
        'statement_id',
        'transaction_id',
        'card_id',
        'json_data',
    ];

    protected $casts = [
        'json_data' => 'array', // Automatically cast json_data to array
    ];
}
