<?php

namespace App\Modules\LiveTransactions\Models;

use App\Models\AccountTransaction;
use App\Models\TRRequest;
use Illuminate\Database\Eloquent\Model;

class LiveTransactionFeed extends Model
{
    protected $table = 'live_transaction_feed';

    protected $fillable = [
        'stream_key',
        'tr_request_id',
        'account_transaction_id',
        'fireblocks_tx_id',
        'liquidity_order_id',
        'portal_source',
        'flow_from',
        'flow_to',
        'transaction_nature',
        'transaction_type',
        'asset_code',
        'asset_name',
        'network_name',
        'amount_display',
        'fiat_display',
        'status_label',
        'status_tone',
        'progress_percent',
        'ledger_url',
        'initiator_name',
        'client_name',
        'account_label',
        'is_crypto',
        'direction',
        'meta',
        'occurred_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'occurred_at' => 'datetime',
        'progress_percent' => 'integer',
        'is_crypto' => 'boolean',
    ];

    public function trRequest()
    {
        return $this->belongsTo(TRRequest::class, 'tr_request_id');
    }

    public function accountTransaction()
    {
        return $this->belongsTo(AccountTransaction::class, 'account_transaction_id');
    }
}
