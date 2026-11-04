<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenPaydBankAccount extends Model
{
    use HasFactory;

    protected $table = 'openpayd_bank_account';

    protected $fillable = [
        'openpayd_linked_client_id',
        'account_id',
        'client_id',
        'openpayd_account_id',
        'currency',
        'status',
        'internal_account_id',
        'bank_country',
        'bank_address',
        'iban',
        'bic',
        'account_number',
        'bank_name',
        'bank_account_holder_name',
        'provider',
        'payment_type',
        'domestic',
        'routing_code_entries',
        'pay_in_reference',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }
}
