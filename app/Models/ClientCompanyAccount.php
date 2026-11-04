<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientCompanyAccount extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table = "client_accounts";

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'account_currency');
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'company_id', 'id');

    }

    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class, 'company_id', 'id');
    }
}
