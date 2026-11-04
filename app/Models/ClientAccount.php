<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAccount extends Model
{
    use HasFactory;

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'account_currency');
    }

    public function transactions()
    {
        return $this->hasMany(ClientAccountTransaction::class, 'account_id', 'id');
    }
}
