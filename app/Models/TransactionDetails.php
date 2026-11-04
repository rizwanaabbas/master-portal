<?php

namespace App\Models;

use App\Helpers\ChangeLogger;
use App\Helpers\Common;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Mews\Purifier\Casts\CleanHtml;
use OwenIt\Auditing\Contracts\Auditable;

class TransactionDetails extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = "transaction_details";

    protected $fillable = [
        'account_id',
        'amount',
        'service_fee',
        'trn_fee',
        'transaction_date',
        'remarks',
        'service_fee_remarks',
        'trn_fee_remarks'
    ];

    protected $casts = [
        'account_id' => CleanHtml::class,
        'service_fee' => 'double',
        'trn_fee' => 'double',
        'transaction_date' => CleanHtml::class,
        'remarks' => CleanHtml::class,
        'service_fee_remarks' => CleanHtml::class,
        'trn_fee_remarks' => CleanHtml::class,
    ];

    public static function boot()
    {
        parent::boot();

        self::updating(function ($model) {

            // track changes only after approval, no need to track changes before approval
            // log changes for approval if user can't make the change
            Log::info("\n\n\n\n\n\n");
            Log::info(__METHOD__ . ' | ' . __LINE__);
            if (!Common::canUserMakeChange()) {
                Log::info(__METHOD__ . ' | ' . __LINE__);
                Log::info("\n\n\n\n\n\n");
                return ChangeLogger::log($model, "Transaction Details updated", url()->previous());
                // Log::info(__METHOD__ . ' | ' . __LINE__);
                // return true;
            }
        });
    }

    public function reviewer()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
