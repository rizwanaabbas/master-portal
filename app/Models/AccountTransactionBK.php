<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountTransactionBK extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table = "account_transactions";

    public function credit_account()
    {
        return $this->hasOne(Account::class, 'id', 'credit_account_id');
    }

    public function debit_account()
    {
        return $this->hasOne(Account::class, 'id', 'debit_account_id');
    }

    public function cheque()
    {
        return $this->hasOne(AccountTransactionCheque::class, 'transaction_id', 'id');
    }
}
