<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;
    public $timestamps = true;

    public function bank()
    {
        return $this->hasOne(Bank::class, 'id', 'bank_id');
    }

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'account_currency');
    }
}
