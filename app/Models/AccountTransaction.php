<?php

namespace App\Models;

use App\Helpers\ChangeLogger;
use App\Helpers\Common;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Casts\CleanHtml;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Builder;

class AccountTransaction extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    public $timestamps = true;
    protected $table = "account_transactions";

    protected $fillable = ["account_id", "currency_id", "transaction_type", "transaction_nature", "transaction_date", "amount", "remarks", "status", "reject_reason"];

    protected $casts = [
        'account_id' => CleanHtml::class,
        'amount' => 'double',
        'transaction_date' => CleanHtml::class,
        'transaction_type' => CleanHtml::class,
        'remarks' => CleanHtml::class,
    ];

    public static function boot()
    {
        parent::boot();

        self::updating(function ($model) {

            // track changes only after approval, no need to track changes before approval
            if ($model->status == "Approved" && $model->approved_by != 0) {
                // log changes for approval if user can't make the change
                if (!Common::canUserMakeChange()) {
                    ChangeLogger::log($model, "Transaction updated", url()->previous());
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

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }

    public function cheque()
    {
        return $this->hasOne(AccountTransactionCheque::class, 'transaction_id', 'id');
    }

    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }

    public function requestedTrn()
    {
        return $this->hasOne(TRRequest::class, 'id', 'tr_request_id');
    }

    public function beneficiary()
    {
        return $this->hasOne(ClientBeneficiary::class, 'id', 'beneficiary_id');
    }

    public function approver()
    {
        return $this->hasOne(User::class, 'id', 'approved_by');
    }

    /**
     * Fireblocks and Aquanow (liquidity) settle without a human checker,
     * so the UI shows "Automated Approval" instead of the initiating user.
     */
    public static function usesAutomatedApproval(?string $cryptoSystem): bool
    {
        return in_array(strtolower(trim((string) $cryptoSystem)), ['fireblocks', 'aquanow'], true);
    }

    public function displayApproverName(): ?string
    {
        if (strcasecmp((string) $this->status, 'Approved') !== 0) {
            return null;
        }

        if (self::usesAutomatedApproval($this->crypto_system)) {
            return 'Automated Approval';
        }

        if ($this->approved_by !== null && (int) $this->approved_by === 0) {
            return 'Automated Approval';
        }

        return $this->approver->name ?? null;
    }

    public function rejector()
    {
        return $this->hasOne(User::class, 'id', 'rejected_by');
    }

    public function linkedTrn()
    {
        return $this->hasOne(AccountTransaction::class, 'id', 'link_tr_id');
    }

    public function linkedAccTrn()
    {
        return $this->belongsTo(AccountTransaction::class, 'link_tr_id', 'id');
    }

    public function docs()
    {
        return $this->hasMany(TRRequestDoc::class, 'acc_trn_unique_id', 'docs_unique_id');
    }

    public function parentTransaction()
    {
        return $this->hasOne(AccountTransaction::class, 'id', 'parent_trn_id');
    }

    public function method()
    {
        return $this->hasOne(TransactionMethod::class, 'id', 'transaction_method_id');
    }

    public function childTransactions($parentTrnId)
    {
        return AccountTransaction::where('parent_trn_id', $parentTrnId)->get();
    }
}
