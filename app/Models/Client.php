<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ChangeLogger;
use App\Helpers\Common;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Mews\Purifier\Casts\CleanHtml;
use OwenIt\Auditing\Contracts\Auditable;
use App\Modules\Aml\Controllers\AmlRuleController;


class Client extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    public $timestamps = true;

    protected $fillable = ['name', 'type', 'status', 'send_welcome_email', 'send_ssi', 'create_user','owner_user_id','unit_id'];

    protected $casts = [
        'name' => CleanHtml::class
    ];

    public function getNameAttribute($value)
    {
        return str_replace("&amp;", "&", $value);
    }

    public static function boot()
    {
        parent::boot();

        self::updating(function ($model) {

            // track changes only after approval, no need to track changes before approval
            if ($model->status == "Approved") {
                // log changes for approval if user can't make the change
                if (!Common::canUserMakeChange() && !empty(Auth::user())) {
                    ChangeLogger::log($model, "Update client", url()->previous());
                    return false;
                }
            }
        });
    }
    
    protected static function booted(): void
    {
        static::addGlobalScope('basic_admin_owner_restriction', function (Builder $builder) {
            $user = Auth::user();
            
            if ($user && $user->hasRole('basic admin')) {
                if(!empty($user->unit->id)) {
                    $unit_id = $user->unit->id;
                    //$user_ids = User::where('unit_id', $unit_id)->pluck('id')->toArray();
                    // Filter builder by users in this unit
                    $builder->where('clients.unit_id', $unit_id);
                } else {
                    //$builder->where('clients.owner_user_id', $user->id);
                    $builder->whereRaw('0 = 1'); // no unit, deny all
                }
            }
        });
    }

    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }
    
    public function unit()
    {
        return $this->hasOne(Unit::class, 'id', 'unit_id');
    }

    public function approver()
    {
        return $this->hasOne(User::class, 'id', 'approved_by');
    }

    public function individual()
    {
        return $this->hasOne(ClientIndividual::class, 'client_id', 'id');
    }

    public function allTransactions()
    {
        //return $this->hasMany()
    }

    public function users()
    {
        return $this->hasMany(ClientUser::class, 'client_id', 'id');
    }

    public function company()
    {
        return $this->hasOne(ClientCompany::class, 'client_id', 'id');
    }

    public function clientAccounts()
    {
        return $this->hasMany(Account::class, 'client_id', 'id');
    }

    public function draftCurrencies()
    {
        return $this->hasMany(ClientDraftCurrency::class, 'client_id', 'id');
    }

    public function feeTierGroup()
    {
        return $this->belongsTo(\App\Modules\FeeEngine\Models\FeeTierGroup::class, 'fee_tier_group_id');
    }

    public function closer()
    {
        return $this->hasOne(User::class, 'id', 'closed_by');
    }

    public function cardLinks()
    {
        return $this->hasMany(UserCardLink::class, 'client_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(Client::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Client::class, 'parent_id');
    }

    public function clientKYC()
    {
        return $this->belongsTo(ClientKYC::class, 'client_id');
    }
    public function directors()
    {
        return $this->hasMany(ClientDirector::class, 'client_id', 'id')->where('is_deleted','=', 0);
    }
    
    public function signatories()
    {
        return $this->hasMany(ClientSignatory::class, 'client_id', 'id')->where('is_deleted','=', 0);
    }

    public function clientriskrating()
    {
        return $this->hasOne(ClientRiskRating::class, 'client_id', 'id')->where('status', 'active');
    }

    // ✅ Corrected Accessor Method
    public function getRiskLevelAttribute()
    {
        $risk_score = $this->clientriskrating->risk_score ?? 0;
        
        if (!$risk_score) {
            return "none"; // Default to "Low Risk" instead of returning null
        }

        $score_data = ["total_risk_score" => $risk_score];
        //dd($score_data);
        $aml_risk_results = AmlRuleController::applyRules($score_data, 'Risk Score');
        //dd($aml_risk_results);
        if(isset($aml_risk_results[0])){
            return $aml_risk_results[0]['status'] ?? "none"; // Ensuring it always returns a string
        }
        return  "";
    }

    public function virtualBankAccounts()
    {
        return $this->hasMany(VirtualBankAccount::class, 'client_id');
    }
    
    public function virtualBankClient()
    {
        return $this->hasMany(VirtualBankLinkedClient::class, 'client_id');
    }
}
