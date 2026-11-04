<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientIndividualAccount extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table = "client_accounts";

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'account_currency');
    }

    public function transactions()
    {
        return $this->hasMany(ClientAccountTransaction::class, 'account_id', 'id');
    }
}
