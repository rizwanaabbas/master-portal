<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualBankLinkedClient extends Model
{
    use HasFactory;

    protected $table = 'virtual_bank_linked_clients';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_holder_id',
        'linked_client_id',
        'client_id',
        'status',
        'country',
        'is_uob_client',
        'kyc_status',
        'provider_name',
        'response_data',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }
    
    public function account()
    {
        return $this->belongsTo(Account::class, 'client_id', 'id');
    }
    
    public function allAccounts()
    {
        return $this->hasMany(Account::class, 'client_id', 'client_id');
    }

    public function sepaAccounts()
    {
        return $this->hasMany(Account::class, 'client_id', 'id')
                    ->whereNotNull('virtual_bank_account_id');
    }

    public function bankAccounts()
    {
        return $this->hasMany(VirtualBankAccount::class, 'linked_client_id', 'id');
    }

    public function beneficiaries()
    {
        return $this->hasMany(VirtualBankBeneficiary::class, 'linked_client_id', 'id');
    }
}
