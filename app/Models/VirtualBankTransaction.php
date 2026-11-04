<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualBankTransaction extends Model
{
    use HasFactory;

    protected $table = 'virtual_bank_transactions';
    public $timestamps = true;

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function trRequest()
    {
        return $this->hasOne(TRRequest::class, 'id', 'trn_id');
    }

    public function accountTransaction() 
    {
        return $this->hasOne(AccountTransaction::class, 'id', 'parent_transaction_id');
    }

    public function linkedClient()
    {
        return $this->belongsTo(VirtualBankLinkedClient::class, 'account_holder_id', 'linked_client_id');
    }

    public function account()
    {
        return $this->belongsTo(VirtualBankAccount::class, 'account_id', 'account_id');
    }
    
}
