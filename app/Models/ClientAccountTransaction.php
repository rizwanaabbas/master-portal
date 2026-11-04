<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientAccountTransaction extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = true;
    public $table = "client_account_transactions";

    public function account()
    {
        return $this->hasOne(ClientAccount::class, 'id', 'account_id');
    }

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }

    public function cheque()
    {
        return $this->hasOne(ClientAccountCheque::class, 'transaction_id');
    }


}
