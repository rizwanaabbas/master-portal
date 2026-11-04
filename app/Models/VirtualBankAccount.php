<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualBankAccount extends Model
{
    use HasFactory;

    protected $table = 'virtual_bank_accounts';

    protected $fillable = [
        'linked_client_id',
        'account_id',
        'client_id',
        'virtual_bank_account_id',

        'currency',
        'currency_id',

        'status',
        'internal_account_id',

        'bank_country',
        'bank_address',

        'iban',
        'bic',
        'account_number',

        'bank_name',
        'bank_account_holder_name',

        'provider_name', // openpayd, lorum, etc.

        'payment_type',
        'domestic',

        'routing_code_entries',
        'pay_in_reference',
        'account_info',
        'response_data'
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */
    protected $casts = [
        'routing_code_entries' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function linkedClient()
    {
        $virtualBankProvider = $this->provider_name;
        
        if ($virtualBankProvider === 'openpayd') {
            return $this->belongsTo(VirtualBankLinkedClient::class, 'linked_client_id', 'id')
            ->where('provider_name', $virtualBankProvider);

            // return $this->belongsTo(VirtualBankLinkedClient::class, 'linked_client_id');
        }

        return $this->belongsTo(VirtualBankLinkedClient::class, 'linked_client_id')
        ->where('provider_name', $virtualBankProvider);
    }
}

