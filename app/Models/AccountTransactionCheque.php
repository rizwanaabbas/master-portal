<?php

namespace App\Models;

use App\Helpers\ChangeLogger;
use App\Helpers\Common;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Casts\CleanHtml;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Builder;

class AccountTransactionCheque extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    public $timestamps = true;
    protected $table = "account_transaction_cheques";
    protected $fillable = ["currency_id", "transaction_id", "in_fav_of", "cheque_number", "cheque_date", "cheque_bank"];

    protected $casts = [
        'cheque_bank' => CleanHtml::class,
        'in_fav_of' => CleanHtml::class,
        'cheque_number' => CleanHtml::class,
        'cheque_date' => CleanHtml::class
    ];

    public static function boot()
    {
        parent::boot();

        self::updating(function($model){

            // track changes only after approval, no need to track changes before approval
            if ($model->transaction->status == "Approved"){
                // log changes for approval if user can't make the change
                if (!Common::canUserMakeChange()){
                    ChangeLogger::log($model, "Cheque details updated", url()->previous());
                    return false;
                }
            }
        });
    }

    protected static function booted()
    {
        static::addGlobalScope('not_deleted', function (Builder $builder) {
            $builder->where($builder->getModel()->qualifyColumn('is_deleted'), false);
        });
    }

    public function transaction()
    {
        return $this->hasOne(AccountTransaction::class, 'id', 'transaction_id');
    }
}
