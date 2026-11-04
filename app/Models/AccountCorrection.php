<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountCorrection extends Model
{
    use HasFactory;

    protected $table = "accounts_corrections";

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }

}
