<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileRequest extends Model
{
    use HasFactory;

    public function maker()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function approver()
    {
        return $this->hasOne(User::class, 'id', 'approved_by');
    }

    public function rejector()
    {
        return $this->hasOne(User::class, 'id', 'rejected_by');
    }

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }
    public function cardDetail()
    {
        return $this->hasOne(CardDetail::class, 'mobile_request_id');
    }
}
