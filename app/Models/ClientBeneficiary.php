<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Casts\CleanHtml;

class ClientBeneficiary extends Model
{
    use HasFactory;

    protected $table = "client_beneficiaries";

    protected $casts = [
        'name' => CleanHtml::class
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function transactions_requests()
    {
        return $this->hasMany(TRRequest::class, 'beneficiary_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class, 'beneficiary_id', 'id');
    }
    public function virtualBankBeneficiary()
    {
        return $this->hasOne(VirtualBankBeneficiary::class, 'parent_beneficiary_id', 'id');
    }
    
    public function country()
    {
        return $this->hasOne(Country::class, 'id', 'beneficiary_country');
    }
    
    public function currency()
    {
        return $this->hasOne( Currency::class, 'id', 'account_currency');
    }
    
    public function bankAccountCountry()
    {
        return $this->hasOne(Country::class, 'id', 'bank_account_country');
    }
   
}
