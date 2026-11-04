<?php

namespace App\Models;

use App\Helpers\ChangeLogger;
use App\Helpers\Common;
use App\Helpers\EmailNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Account extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    public $timestamps = true;
    protected $table = "accounts";

    protected $fillable = [
        'client_id',
        'company_id',
        'individual_id',
        'type',
        'title',
        'account_number',
        'account_currency',
        'maintenance_fee',
        'virtual_bank_account_id',
        'is_active',
        'created_by',
        'created_at'
    ];

    public function getTitleAttribute($value)
    {
        return str_replace("&amp;", "&", $value);
    }

    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {

            // track changes only after approval, no need to track changes before approval
            if ($model->client && $model->client->status == "Approved") {

                // send email to manager for approval
                if (!Common::canUserMakeChange()) {
                    EmailNotification::notifyManagerForNewAccount($model);
                    return true;
                }
            }
        });
    }

    protected static function booted()
    {
        static::addGlobalScope('unitClients', function (Builder $builder) {
            $user = Auth::user();

            if ($user && $user->hasRole('basic admin')) {
                $unit_id = optional($user->unit)->id;

                if ($unit_id) {
                    //$user_ids = \App\Models\User::where('unit_id', $unit_id)->pluck('id');
                    $client_ids = \App\Models\Client::where('unit_id', $unit_id)->pluck('id');
                    //dd($client_ids);
                    $builder->whereIn('client_id', $client_ids);
                } else {
                    $builder->whereRaw('0 = 1'); // no unit, deny all
                }
            }
        });
    }

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'account_currency');
    }

    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class, 'account_id', 'id');
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function bank()
    {
        return $this->hasOne(Bank::class, 'id', 'bank_id');
    }

    public function associatedTransactions()
    {
        return $this->hasMany(TRRequest::class, 'receiver_account_number', 'account_number');
    }

    public function bankAccount()
    {   
        $virtual_bank_provider = $this->virtual_bank_provider ?? $this->currency?->virtual_iban_provider;

        return $this->hasOne(VirtualBankAccount::class, 'account_id', 'id')
            ->when($virtual_bank_provider, function ($query) use ($virtual_bank_provider) {
                $query->where('provider_name', $virtual_bank_provider);
            });
    }

    public function linkedClient()
    {
        $virtualBankProvider = $this->virtual_bank_provider ?? $this->currency?->virtual_iban_provider;

        return $this->belongsTo(VirtualBankLinkedClient::class, 'client_id', 'client_id')->where('provider_name', $virtualBankProvider);
    }

}
