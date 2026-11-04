<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountTransactionNote extends Model
{
    use HasFactory;

    protected $table = 'account_transaction_notes';

    protected $fillable = [
        'note',
        'transaction_request_id',
        'is_deleted',
    ];

    public function transactionRequest()
    {
        return $this->belongsTo(TRRequest::class, 'transaction_request_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
