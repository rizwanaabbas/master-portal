<?php

namespace App\Modules\LiveTransactions\Services;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Currency;
use App\Models\FireblocksTransaction;
use App\Modules\LiveTransactions\Models\LiveTransactionFeed;
use App\Models\TRRequest;
use App\Models\User;
use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Modules\Liquidity\Models\Order as LiquidityOrder;
use Illuminate\Support\Facades\Log;

class LiveTransactionFeedService
{
    public function syncFromTrRequest(TRRequest $trRequest, ?string $portalOverride = null): ?LiveTransactionFeed
    {
        try {
            $trRequest->loadMissing(['account.client', 'currency', 'creator']);

            return $this->upsert($this->buildFromTrRequest($trRequest, $portalOverride));
        } catch (\Throwable $e) {
            Log::warning('Live feed sync TRRequest failed', ['id' => $trRequest->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function syncFromAccountTransaction(AccountTransaction $transaction, ?string $portalOverride = null): ?LiveTransactionFeed
    {
        try {
            $transaction->loadMissing(['account.client', 'currency', 'creator', 'requestedTrn']);

            return $this->upsert($this->buildFromAccountTransaction($transaction, $portalOverride));
        } catch (\Throwable $e) {
            Log::warning('Live feed sync AccountTransaction failed', ['id' => $transaction->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function syncFromFireblocks(FireblocksTransaction $fbTx, ?string $portalOverride = null): ?LiveTransactionFeed
    {
        if (FireblocksTransaction::isCustodyOnlyWithoutLedger($fbTx)) {
            return null;
        }

        try {
            $fbTx->loadMissing(['accountTransaction.account.client', 'accountTransaction.currency']);

            return $this->upsert($this->buildFromFireblocks($fbTx, $portalOverride ?? 'Fireblocks'));
        } catch (\Throwable $e) {
            Log::warning('Live feed sync Fireblocks failed', ['id' => $fbTx->fireblocks_tx_id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function syncFromLiquidityOrder(LiquidityOrder $order, ?string $portalOverride = null): ?LiveTransactionFeed
    {
        try {
            return $this->upsert($this->buildFromLiquidityOrder($order, $portalOverride));
        } catch (\Throwable $e) {
            Log::warning('Live feed sync Liquidity order failed', ['id' => $order->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function fetchFeed(?string $since = null, int $page = 1): array
    {
        $config = LiveTransactionsConfigService::tryGet() ?? LiveTransactionsConfigService::defaults();
        $perPage = (int) $config['max_records_per_page'];

        if (!$since && LiveTransactionFeed::count() === 0) {
            $this->bootstrapRecent($perPage * 2);
        } elseif (!$since) {
            try {
                $this->backfillRecentTradeNatures($perPage);
            } catch (\Throwable $e) {
                Log::warning('Live feed trade-nature backfill failed', ['error' => $e->getMessage()]);
            }
        }

        $baseQuery = $this->filteredQuery($config);

        if ($since) {
            $sinceAt = $this->parseFeedCursor($since);
            $items = (clone $baseQuery)
                ->where('updated_at', '>', $sinceAt)
                ->orderByDesc('updated_at')
                ->limit(100)
                ->get()
                ->map(fn (LiveTransactionFeed $item) => $this->serialize($item))
                ->values()
                ->all();

            return [
                'mode' => 'delta',
                'items' => $items,
                'cursor' => $this->resolveFeedCursor($sinceAt, $items),
                'pagination' => $this->paginationMeta($baseQuery, $page, $perPage),
                'pending_count' => $this->pendingCount($baseQuery),
                'config' => $this->clientConfig($config),
            ];
        }

        $paginator = (clone $baseQuery)
            ->orderByDesc('updated_at')
            ->paginate($perPage, ['*'], 'page', max(1, $page));

        $items = collect($paginator->items())
            ->map(fn (LiveTransactionFeed $item) => $this->serialize($item))
            ->values()
            ->all();

        return [
            'mode' => 'page',
            'items' => $items,
            'cursor' => $this->resolveFeedCursor(null, $items),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
            'pending_count' => $this->pendingCount($baseQuery),
            'config' => $this->clientConfig($config),
        ];
    }

    /** @deprecated Use fetchFeed() */
    public function poll(?string $since = null, int $limit = 50): array
    {
        $result = $this->fetchFeed($since, 1);

        return $result['items'];
    }

    protected function filteredQuery(array $config)
    {
        $query = LiveTransactionFeed::query();

        match ($config['asset_filter'] ?? 'both') {
            'crypto_only' => $query->where('is_crypto', true),
            'fiat_only' => $query->where('is_crypto', false),
            default => null,
        };

        match ($config['direction_filter'] ?? 'both') {
            'incoming_only' => $query->where(function ($inner) {
                $inner->where('direction', 'incoming')
                    ->orWhereIn('transaction_nature', ['Buy', 'Sell', 'Swap']);
            }),
            'outgoing_only' => $query->where(function ($inner) {
                $inner->where('direction', 'outgoing')
                    ->orWhereIn('transaction_nature', ['Buy', 'Sell', 'Swap']);
            }),
            default => null,
        };

        return $query;
    }

    protected function paginationMeta($baseQuery, int $page, int $perPage): array
    {
        $total = (clone $baseQuery)->count();
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));

        return [
            'page' => max(1, $page),
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'has_more' => $page < $lastPage,
        ];
    }

    protected function pendingCount($baseQuery): int
    {
        return (clone $baseQuery)->where('status_tone', 'pending')->count();
    }

    protected function clientConfig(array $config): array
    {
        return [
            'max_records_per_page' => (int) $config['max_records_per_page'],
            'poll_interval_open_ms' => (int) $config['poll_interval_open_seconds'] * 1000,
            'poll_interval_closed_ms' => (int) $config['poll_interval_closed_seconds'] * 1000,
            'asset_filter' => $config['asset_filter'],
            'direction_filter' => $config['direction_filter'],
        ];
    }

    protected function withClassification(array $payload, bool $isCrypto, string $direction): array
    {
        $payload['is_crypto'] = $isCrypto;
        $payload['direction'] = $direction;

        return $payload;
    }

    protected function resolveIsCrypto(?Currency $currency, ?TRRequest $tr = null, bool $forceCrypto = false): bool
    {
        if ($forceCrypto) {
            return true;
        }

        if ($currency && (bool) $currency->is_crypto) {
            return true;
        }

        if ($tr && in_array($tr->transaction_nature, ['Buy', 'Sell', 'Deposit', 'Withdraw', 'Swap'], true)) {
            return true;
        }

        return false;
    }

    protected function resolveDirectionFromTr(TRRequest $tr): string
    {
        $nature = strtolower((string) $tr->transaction_nature);

        if ($nature === 'deposit') {
            return 'incoming';
        }

        if ($nature === 'withdraw') {
            return 'outgoing';
        }

        if ($nature === 'buy') {
            return 'incoming';
        }

        if ($nature === 'sell') {
            return 'outgoing';
        }

        if ($nature === 'swap') {
            return 'internal';
        }

        if (strtolower((string) $tr->transaction_type) === 'credit') {
            return 'incoming';
        }

        if (strtolower((string) $tr->transaction_type) === 'debit') {
            return 'outgoing';
        }

        return 'internal';
    }

    protected function resolveDirectionFromAccountTransaction(AccountTransaction $trn): string
    {
        if ($trn->requestedTrn) {
            return $this->resolveDirectionFromTr($trn->requestedTrn);
        }

        $nature = strtolower((string) $trn->transaction_nature);
        if ($nature === 'deposit') {
            return 'incoming';
        }
        if ($nature === 'withdraw') {
            return 'outgoing';
        }
        if ($nature === 'buy') {
            return 'incoming';
        }
        if ($nature === 'sell') {
            return 'outgoing';
        }
        if ($nature === 'swap') {
            return 'internal';
        }

        return strtolower((string) $trn->transaction_type) === 'credit' ? 'incoming' : 'outgoing';
    }

    protected function resolveDirectionFromFireblocks(FireblocksTransaction $fb): string
    {
        $dir = strtoupper((string) $fb->direction);

        if ($dir === 'INCOMING') {
            return 'incoming';
        }

        if ($dir === 'OUTGOING') {
            return 'outgoing';
        }

        return 'internal';
    }

    protected function resolveDirectionFromLiquidity(LiquidityOrder $order): string
    {
        return strtoupper((string) $order->side) === 'BUY' ? 'incoming' : 'outgoing';
    }

    public function bootstrapRecent(int $limit = 50): void
    {
        if (LiveTransactionFeed::count() > 0) {
            return;
        }

        TRRequest::with(['account.client', 'currency', 'creator'])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->each(fn (TRRequest $tr) => $this->syncFromTrRequest($tr));

        FireblocksTransaction::with(['accountTransaction'])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->each(fn (FireblocksTransaction $fb) => $this->syncFromFireblocks($fb));

        LiquidityOrder::orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->each(fn (LiquidityOrder $order) => $this->syncFromLiquidityOrder($order));

        AccountTransaction::with(['account.client', 'currency', 'creator', 'requestedTrn'])
            ->whereIn('transaction_nature', ['Buy', 'Sell', 'Swap'])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->each(fn (AccountTransaction $trn) => $this->syncFromAccountTransaction($trn));
    }

    /**
     * Existing feeds were often bootstrapped from Fireblocks deposit/withdraw only.
     * Pull recent Buy / Sell / Swap (ledger + liquidity) so the live tray includes them.
     */
    protected function backfillRecentTradeNatures(int $limit = 30): void
    {
        TRRequest::with(['account.client', 'currency', 'creator'])
            ->whereIn('transaction_nature', ['Buy', 'Sell', 'Swap'])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->each(fn (TRRequest $tr) => $this->syncFromTrRequest($tr));

        AccountTransaction::with(['account.client', 'currency', 'creator', 'requestedTrn'])
            ->whereIn('transaction_nature', ['Buy', 'Sell', 'Swap'])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->each(fn (AccountTransaction $trn) => $this->syncFromAccountTransaction($trn));

        LiquidityOrder::orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->each(fn (LiquidityOrder $order) => $this->syncFromLiquidityOrder($order));
    }

    protected function upsert(array $payload): LiveTransactionFeed
    {
        $record = LiveTransactionFeed::updateOrCreate(
            ['stream_key' => $payload['stream_key']],
            $payload
        );

        $this->purgeSupersededFeedRows($record, $payload);

        return $record->fresh();
    }

    protected function purgeSupersededFeedRows(LiveTransactionFeed $record, array $payload): void
    {
        $query = LiveTransactionFeed::query()
            ->where('id', '!=', $record->id)
            ->where('stream_key', '!=', $record->stream_key);

        if (!empty($payload['tr_request_id'])) {
            $query->where(function ($builder) use ($payload) {
                $builder->where('tr_request_id', $payload['tr_request_id']);

                if (!empty($payload['fireblocks_tx_id'])) {
                    $builder->orWhere('fireblocks_tx_id', $payload['fireblocks_tx_id']);
                }

                if (!empty($payload['account_transaction_id'])) {
                    $builder->orWhere('account_transaction_id', $payload['account_transaction_id']);
                }

                if (!empty($payload['liquidity_order_id'])) {
                    $builder->orWhere('liquidity_order_id', $payload['liquidity_order_id']);
                }
            });
        } elseif (!empty($payload['fireblocks_tx_id'])) {
            $query->where('fireblocks_tx_id', $payload['fireblocks_tx_id']);
        } elseif (!empty($payload['account_transaction_id'])) {
            $query->where('account_transaction_id', $payload['account_transaction_id']);
        } elseif (!empty($payload['liquidity_order_id'])) {
            $query->where('liquidity_order_id', $payload['liquidity_order_id']);
        } else {
            return;
        }

        $query->delete();
    }

    protected function parseFeedCursor(string $since): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($since);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function resolveFeedCursor(?\Carbon\Carbon $previousCursor, array $items): string
    {
        $latest = collect($items)
            ->pluck('updated_at')
            ->filter()
            ->map(fn ($value) => \Carbon\Carbon::parse($value))
            ->max();

        if ($latest instanceof \Carbon\Carbon) {
            return $latest->toIso8601String();
        }

        if ($previousCursor instanceof \Carbon\Carbon) {
            return $previousCursor->toIso8601String();
        }

        return now()->toIso8601String();
    }

    protected function buildFromTrRequest(TRRequest $tr, ?string $portalOverride = null): array
    {
        $account = $tr->account;
        $client = $account?->client;
        $currency = $tr->currency;
        $isCrypto = in_array($tr->transaction_nature, ['Buy', 'Sell', 'Deposit', 'Withdraw', 'Swap'], true);

        $flow = $this->resolveFlow($tr, null, null);
        $status = (string) ($tr->status ?? 'Pending');
        $tone = $this->toneForStatus($status);

        return $this->withClassification([
            'stream_key' => 'tr:' . $tr->id,
            'tr_request_id' => $tr->id,
            'account_transaction_id' => null,
            'fireblocks_tx_id' => $tr->crypto_transaction_id ?: null,
            'liquidity_order_id' => is_numeric($tr->crypto_details_id) ? (int) $tr->crypto_details_id : null,
            'portal_source' => $portalOverride ?? $this->resolvePortal($tr->creator, $tr->crypto_system),
            'flow_from' => $flow['from'],
            'flow_to' => $flow['to'],
            'transaction_nature' => $tr->transaction_nature,
            'transaction_type' => $tr->transaction_type,
            'asset_code' => $currency?->short,
            'asset_name' => $currency?->name,
            'network_name' => $tr->crypto_network ?: null,
            'amount_display' => $this->formatAmount($tr->crypto_amount ?: $tr->amount, $currency?->short, (bool) $currency?->is_crypto),
            'fiat_display' => $isCrypto && $tr->amount ? $this->formatFiat($tr->amount, 'USD') : null,
            'status_label' => $status,
            'status_tone' => $tone,
            'progress_percent' => $this->progressForInternalStatus($status),
            'ledger_url' => $this->ledgerUrlForTrRequest($tr),
            'initiator_name' => $tr->creator?->name,
            'client_name' => $client?->name,
            'account_label' => $account ? ($account->account_number . ' · ' . ($client?->name ?? '')) : null,
            'meta' => [
                'stage' => 'request',
                'crypto_system' => $tr->crypto_system,
                'wallet' => $tr->to_wallet_address,
            ],
            'occurred_at' => $tr->updated_at ?? $tr->created_at,
        ], $this->resolveIsCrypto($currency, $tr), $this->resolveDirectionFromTr($tr));
    }

    protected function buildFromAccountTransaction(AccountTransaction $trn, ?string $portalOverride = null): array
    {
        $trRequest = $trn->requestedTrn;
        if ($trRequest) {
            $payload = $this->buildFromTrRequest($trRequest, $portalOverride);
            $payload['stream_key'] = 'tr:' . $trRequest->id;
            $payload['account_transaction_id'] = $trn->id;
            $payload['status_label'] = (string) ($trn->status ?? $payload['status_label']);
            $payload['status_tone'] = $this->toneForStatus($payload['status_label']);
            $payload['progress_percent'] = max(
                $payload['progress_percent'],
                $this->progressForInternalStatus($payload['status_label'])
            );
            $payload['ledger_url'] = $this->ledgerUrlForAccountTransaction($trn);
            $payload['meta'] = array_merge($payload['meta'] ?? [], [
                'stage' => 'ledger',
                'account_transaction_id' => $trn->id,
            ]);
            $payload['occurred_at'] = $trn->updated_at ?? $trn->created_at;

            return $payload;
        }

        $account = $trn->account;
        $client = $account?->client;
        $currency = $trn->currency;
        $status = (string) ($trn->status ?? 'Pending');

        return $this->withClassification([
            'stream_key' => 'at:' . $trn->id,
            'tr_request_id' => $trn->tr_request_id,
            'account_transaction_id' => $trn->id,
            'fireblocks_tx_id' => $trn->crypto_transaction_id ?: null,
            'liquidity_order_id' => is_numeric($trn->crypto_details_id) ? (int) $trn->crypto_details_id : null,
            'portal_source' => $portalOverride ?? $this->resolvePortal($trn->creator, $trn->crypto_system),
            'flow_from' => $this->flowLabelForNature($trn->transaction_nature, 'from'),
            'flow_to' => $account?->account_number ? 'Account: ' . $account->account_number : 'Account ledger',
            'transaction_nature' => $trn->transaction_nature,
            'transaction_type' => $trn->transaction_type,
            'asset_code' => $currency?->short,
            'asset_name' => $currency?->name,
            'network_name' => null,
            'amount_display' => $this->formatAmount($trn->amount, $currency?->short, (bool) $currency?->is_crypto),
            'fiat_display' => null,
            'status_label' => $status,
            'status_tone' => $this->toneForStatus($status),
            'progress_percent' => $this->progressForInternalStatus($status),
            'ledger_url' => $this->ledgerUrlForAccountTransaction($trn),
            'initiator_name' => $trn->creator?->name,
            'client_name' => $client?->name,
            'account_label' => $account ? ($account->account_number . ' · ' . ($client?->name ?? '')) : null,
            'meta' => ['stage' => 'ledger'],
            'occurred_at' => $trn->updated_at ?? $trn->created_at,
        ], $this->resolveIsCrypto($currency, null, in_array($trn->transaction_nature, ['Buy', 'Sell', 'Deposit', 'Withdraw', 'Swap'], true)), $this->resolveDirectionFromAccountTransaction($trn));
    }

    protected function buildFromFireblocks(FireblocksTransaction $fb, ?string $portalOverride = null): array
    {
        $accountTrn = $fb->accountTransaction;
        $trRequest = $accountTrn?->tr_request_id ? TRRequest::find($accountTrn->tr_request_id) : null;
        $streamKey = $trRequest ? 'tr:' . $trRequest->id : 'fb:' . $fb->fireblocks_tx_id;

        $status = (string) ($fb->status ?? 'SUBMITTED');
        $internal = FireblocksStatusHelper::mapToInternalStatus($status);
        $tone = $this->toneForFireblocks($status);
        $progress = $this->progressForFireblocksStatus($status);

        $assetCode = $this->extractAssetCode($fb->asset_id);
        $flowFrom = $this->formatVaultLabel($fb->source_vault_account_id, 'External');
        $flowTo = $fb->destination_address
            ? ('Address: ' . $this->truncate($fb->destination_address, 18))
            : $this->formatVaultLabel($fb->source_vault_account_id, 'Vault');

        if (strtoupper((string) $fb->direction) === 'INCOMING') {
            $flowFrom = 'External';
            $flowTo = $this->formatVaultLabel($fb->source_vault_account_id, 'Vault account');
        } elseif (strtoupper((string) $fb->direction) === 'OUTGOING') {
            $flowFrom = $this->formatVaultLabel($fb->source_vault_account_id, 'Vault account');
            $flowTo = $fb->destination_address
                ? ('Address: ' . $this->truncate($fb->destination_address, 18))
                : 'External';
        }

        $payload = [
            'stream_key' => $streamKey,
            'tr_request_id' => $trRequest?->id,
            'account_transaction_id' => $accountTrn?->id,
            'fireblocks_tx_id' => $fb->fireblocks_tx_id,
            'liquidity_order_id' => null,
            'portal_source' => $portalOverride ?? 'Fireblocks',
            'flow_from' => $flowFrom,
            'flow_to' => $flowTo,
            'transaction_nature' => $trRequest?->transaction_nature ?? ucfirst(strtolower((string) $fb->direction)),
            'transaction_type' => $accountTrn?->transaction_type,
            'asset_code' => $assetCode,
            'asset_name' => $fb->asset_id,
            'network_name' => $this->networkFromAsset($fb->asset_id),
            'amount_display' => $this->formatAmount($fb->amount, $assetCode, true),
            'fiat_display' => null,
            'status_label' => $this->humanizeFireblocksStatus($status, $fb->sub_status),
            'status_tone' => $tone,
            'progress_percent' => $progress,
            'ledger_url' => $accountTrn
                ? $this->ledgerUrlForAccountTransaction($accountTrn)
                : ($trRequest ? $this->ledgerUrlForTrRequest($trRequest) : url('/fireblocks/transaction-monitoring')),
            'initiator_name' => $trRequest?->creator?->name,
            'client_name' => $accountTrn?->account?->client?->name,
            'account_label' => $accountTrn?->account?->account_number,
            'meta' => [
                'stage' => 'fireblocks',
                'fireblocks_status' => $status,
                'fireblocks_sub_status' => $fb->sub_status,
                'internal_status' => $internal,
                'tx_hash' => $fb->tx_hash,
            ],
            'occurred_at' => $fb->updated_at ?? $fb->created_at,
        ];

        if ($trRequest) {
            $merged = $this->buildFromTrRequest($trRequest, $portalOverride ?? 'Fireblocks');
            $merged = array_merge($merged, $payload);
            $merged['stream_key'] = 'tr:' . $trRequest->id;
            $merged['progress_percent'] = max($merged['progress_percent'], $progress);
            $merged['status_label'] = $payload['status_label'];
            $merged['status_tone'] = $tone;
            $merged['is_crypto'] = true;
            $merged['direction'] = $this->resolveDirectionFromFireblocks($fb);

            return $merged;
        }

        return $this->withClassification($payload, true, $this->resolveDirectionFromFireblocks($fb));
    }

    protected function buildFromLiquidityOrder(LiquidityOrder $order, ?string $portalOverride = null): array
    {
        $trRequest = $order->tr_request_id ? TRRequest::with(['account.client', 'currency', 'creator'])->find($order->tr_request_id) : null;
        $streamKey = $trRequest ? 'tr:' . $trRequest->id : 'lq:' . $order->id;
        $status = (string) ($order->status ?? 'Pending');
        $instrument = (string) ($order->instrument ?? '');

        $payload = [
            'stream_key' => $streamKey,
            'tr_request_id' => $order->tr_request_id,
            'account_transaction_id' => $order->dr_tr_id ?: $order->cr_tr_id,
            'fireblocks_tx_id' => null,
            'liquidity_order_id' => $order->id,
            'portal_source' => $portalOverride ?? ('Liquidity · ' . strtoupper((string) $order->provider)),
            'flow_from' => strtoupper((string) $order->side) === 'BUY' ? 'Fiat' : 'Crypto',
            'flow_to' => strtoupper((string) $order->side) === 'BUY' ? 'Crypto' : 'Fiat',
            'transaction_nature' => $trRequest?->transaction_nature ?? (strtoupper((string) $order->side) === 'BUY' ? 'Buy' : 'Sell'),
            'transaction_type' => $trRequest?->transaction_type,
            'asset_code' => $this->extractInstrumentBase($instrument),
            'asset_name' => $instrument,
            'network_name' => null,
            'amount_display' => $this->formatAmount($order->quantity, $this->extractInstrumentBase($instrument), true),
            'fiat_display' => $order->grand_total ? $this->formatFiat($order->grand_total, 'USD') : null,
            'status_label' => $status,
            'status_tone' => $this->toneForLiquidityStatus($status),
            'progress_percent' => $this->progressForLiquidityStatus($status),
            'ledger_url' => $trRequest
                ? $this->ledgerUrlForTrRequest($trRequest)
                : ($order->dr_tr_id ? url('/crypto-management/crypto-transactions/edit/crypto-transaction/' . $order->dr_tr_id) : null),
            'initiator_name' => $trRequest?->creator?->name,
            'client_name' => $trRequest?->account?->client?->name,
            'account_label' => $trRequest?->account?->account_number,
            'meta' => [
                'stage' => 'liquidity',
                'provider' => $order->provider,
                'order_id' => $order->order_id,
                'side' => $order->side,
            ],
            'occurred_at' => $order->updated_at ?? $order->created_at,
        ];

        if ($trRequest) {
            $base = $this->buildFromTrRequest($trRequest, $portalOverride ?? $payload['portal_source']);
            $base = array_merge($base, $payload);
            $base['stream_key'] = 'tr:' . $trRequest->id;
            $base['progress_percent'] = max($base['progress_percent'], $payload['progress_percent']);
            $base['is_crypto'] = true;
            $base['direction'] = $this->resolveDirectionFromLiquidity($order);

            return $base;
        }

        return $this->withClassification($payload, true, $this->resolveDirectionFromLiquidity($order));
    }

    protected function serialize(LiveTransactionFeed $item): array
    {
        return [
            'id' => $item->id,
            'stream_key' => $item->stream_key,
            'tr_request_id' => $item->tr_request_id,
            'account_transaction_id' => $item->account_transaction_id,
            'fireblocks_tx_id' => $item->fireblocks_tx_id,
            'liquidity_order_id' => $item->liquidity_order_id,
            'portal_source' => $item->portal_source,
            'flow_from' => $item->flow_from,
            'flow_to' => $item->flow_to,
            'transaction_nature' => $item->transaction_nature,
            'transaction_type' => $item->transaction_type,
            'direction' => $item->direction,
            'direction_label' => $this->directionLabel($item->direction),
            'transaction_type_label' => $this->transactionTypeLabel($item->transaction_nature),
            'asset_code' => $item->asset_code,
            'asset_name' => $item->asset_name,
            'network_name' => $item->network_name,
            'amount_display' => $item->amount_display,
            'fiat_display' => $item->fiat_display,
            'status_label' => $item->status_label,
            'status_tone' => $item->status_tone,
            'progress_percent' => $item->progress_percent,
            'ledger_url' => $this->resolveLedgerUrlForFeedItem($item),
            'initiator_name' => $item->initiator_name,
            'client_name' => $item->client_name,
            'account_label' => $item->account_label,
            'is_crypto' => (bool) $item->is_crypto,
            'meta' => $item->meta,
            'occurred_at' => optional($item->occurred_at)->toIso8601String(),
            'updated_at' => optional($item->updated_at)->toIso8601String(),
            'time_label' => optional($item->occurred_at)->format('g:i A'),
        ];
    }

    protected function resolveLedgerUrlForFeedItem(LiveTransactionFeed $item): ?string
    {
        if ($item->account_transaction_id) {
            $accountTransaction = AccountTransaction::find($item->account_transaction_id);
            if ($accountTransaction) {
                return $this->ledgerUrlForAccountTransaction($accountTransaction);
            }
        }

        if ($item->tr_request_id) {
            $trRequest = TRRequest::find($item->tr_request_id);
            if ($trRequest) {
                return $this->ledgerUrlForTrRequest($trRequest);
            }
        }

        return $item->ledger_url ?: null;
    }

    protected function directionLabel(?string $direction): string
    {
        return match (strtolower((string) $direction)) {
            'incoming' => 'Incoming',
            'outgoing' => 'Outgoing',
            default => 'Internal',
        };
    }

    protected function transactionTypeLabel(?string $nature): string
    {
        if (!$nature) {
            return '—';
        }

        $normalized = strtolower(trim($nature));

        return match ($normalized) {
            'deposit' => 'Deposit',
            'withdraw' => 'Withdraw',
            'buy' => 'Buy',
            'sell' => 'Sell',
            'swap' => 'Swap',
            'internal' => 'Internal',
            'tt' => 'TT',
            'cheque' => 'Cheque',
            'maintenance fee' => 'Maintenance Fee',
            default => ucwords($nature),
        };
    }

    protected function resolvePortal(?User $creator, ?string $cryptoSystem): string
    {
        if ($cryptoSystem && strtolower($cryptoSystem) === 'fireblocks') {
            return 'Fireblocks';
        }

        if ($creator?->client_id) {
            return 'Client Portal';
        }

        return 'Admin Portal';
    }

    protected function resolveFlow(TRRequest $tr, ?FireblocksTransaction $fb, ?AccountTransaction $at): array
    {
        $nature = strtolower((string) $tr->transaction_nature);
        $wallet = $tr->to_wallet_address ? $this->truncate($tr->to_wallet_address, 20) : null;

        if (in_array($nature, ['deposit'], true)) {
            return ['from' => 'External', 'to' => $wallet ? ('Wallet: ' . $wallet) : ('Account: ' . ($tr->account?->account_number ?? 'Client'))];
        }

        if (in_array($nature, ['withdraw'], true)) {
            return [
                'from' => 'Vault: ' . ($tr->account?->account_number ?? 'Client account'),
                'to' => $wallet ? ('Address: ' . $wallet) : 'External',
            ];
        }

        if (in_array($nature, ['buy', 'sell', 'swap'], true)) {
            return [
                'from' => ucfirst($nature) . ' request',
                'to' => 'Liquidity / Ledger',
            ];
        }

        return [
            'from' => $this->flowLabelForNature($tr->transaction_nature, 'from'),
            'to' => $tr->account?->account_number ? ('Account: ' . $tr->account->account_number) : 'Account ledger',
        ];
    }

    protected function flowLabelForNature(?string $nature, string $side): string
    {
        $nature = strtolower((string) $nature);

        return match ($nature) {
            'deposit' => $side === 'from' ? 'External' : 'Client account',
            'withdraw' => $side === 'from' ? 'Client account' : 'External',
            'buy' => $side === 'from' ? 'Fiat' : 'Crypto',
            'sell' => $side === 'from' ? 'Crypto' : 'Fiat',
            'swap' => $side === 'from' ? 'From asset' : 'To asset',
            default => $side === 'from' ? 'Source' : 'Destination',
        };
    }

    protected function ledgerUrlForTrRequest(TRRequest $tr): ?string
    {
        $cryptoNatures = ['Buy', 'Sell', 'Deposit', 'Withdraw', 'Swap'];

        if (in_array($tr->transaction_nature, $cryptoNatures, true)) {
            return $this->ledgerUrlForCryptoTrRequest($tr);
        }

        return $this->ledgerUrlForFiatTrRequest($tr);
    }

    protected function ledgerUrlForCryptoTrRequest(TRRequest $tr): string
    {
        $status = (string) ($tr->status ?? 'Pending');

        if ($status === 'Rejected') {
            return url('/crypto-management/crypto-rejected-transactions/view/crypto-transaction/' . $tr->id);
        }

        $inProgressStatuses = ['Pending', 'Initiated', 'Initial Review', 'Pending OTP', 'Needs Review'];
        if (in_array($status, $inProgressStatuses, true)) {
            return url('/crypto-management/crypto-pending-transactions/view/crypto-transaction/' . $tr->id);
        }

        $ledger = AccountTransaction::where('tr_request_id', $tr->id)->orderByDesc('id')->first();
        if ($ledger) {
            return $this->ledgerUrlForAccountTransaction($ledger);
        }

        return url('/crypto-management/crypto-pending-transactions/view/crypto-transaction/' . $tr->id);
    }

    protected function ledgerUrlForFiatTrRequest(TRRequest $tr): string
    {
        $status = (string) ($tr->status ?? 'Pending');

        if ($status === 'Initial Review') {
            return url('/finance-management/initial-review-transactions/view/' . $tr->id);
        }

        $natureSlug = strtolower((string) $tr->transaction_nature);

        return url('/finance-management/pending-transactions/view/' . $natureSlug . '/' . $tr->id);
    }

    protected function ledgerUrlForAccountTransaction(AccountTransaction $trn): ?string
    {
        $trRequest = $trn->relationLoaded('requestedTrn')
            ? $trn->requestedTrn
            : ($trn->tr_request_id ? TRRequest::find($trn->tr_request_id) : null);

        $inProgressStatuses = ['Pending', 'Initiated', 'Initial Review', 'Pending OTP', 'Needs Review'];
        if ($trRequest && in_array((string) $trRequest->status, $inProgressStatuses, true)) {
            return $this->ledgerUrlForTrRequest($trRequest);
        }

        $cryptoNatures = ['Buy', 'Sell', 'Deposit', 'Withdraw', 'Swap'];
        if (in_array($trn->transaction_nature, $cryptoNatures, true)) {
            return url('/crypto-management/crypto-transactions/edit/crypto-transaction/' . $trn->id);
        }

        return $this->ledgerUrlForFiatAccountTransaction($trn);
    }

    protected function ledgerUrlForFiatAccountTransaction(AccountTransaction $trn): string
    {
        $nature = (string) $trn->transaction_nature;

        if ($nature === 'Maintenance Fee') {
            return url('/finance-management/transaction/maintenance-fees/edit/tt/' . $trn->id);
        }

        $natureSlug = strtolower($nature);
        $editNatures = ['internal', 'tt', 'cheque'];

        if (in_array($natureSlug, $editNatures, true)) {
            return url('/finance-management/transactions/edit/' . $natureSlug . '/' . $trn->id);
        }

        return url('/finance-management/transactions/edit/internal/' . $trn->id);
    }

    protected function progressForInternalStatus(string $status): int
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'initiated' => 15,
            'initial review' => 30,
            'pending otp' => 45,
            'pending' => 55,
            'needs review' => 75,
            'approved' => 100,
            'rejected', 'cancelled', 'failed' => 100,
            default => 25,
        };
    }

    protected function progressForFireblocksStatus(string $status): int
    {
        $status = strtoupper(trim($status));

        if ($status === 'COMPLETED') {
            return 100;
        }

        if (in_array($status, FireblocksStatusHelper::REJECTED_STATUSES, true)
            || in_array($status, FireblocksStatusHelper::CANCELLED_STATUSES, true)) {
            return 100;
        }

        $pending = FireblocksStatusHelper::PENDING_STATUSES;
        $index = array_search($status, $pending, true);

        if ($index !== false) {
            $max = max(count($pending) - 1, 1);

            return (int) round(20 + ($index / $max) * 70);
        }

        return 40;
    }

    protected function progressForLiquidityStatus(string $status): int
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'pending', 'open', 'submitted' => 35,
            'partially_filled', 'partial' => 65,
            'filled', 'completed', 'approved', 'success' => 100,
            'failed', 'rejected', 'cancelled' => 100,
            default => 45,
        };
    }

    protected function toneForStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        if (in_array($normalized, ['approved', 'completed', 'success', 'filled'], true)) {
            return 'success';
        }

        if (in_array($normalized, ['rejected', 'failed', 'cancelled'], true)) {
            return 'danger';
        }

        if (in_array($normalized, ['needs review'], true)) {
            return 'warning';
        }

        return 'pending';
    }

    protected function toneForFireblocks(string $status): string
    {
        $status = strtoupper($status);

        if (in_array($status, FireblocksStatusHelper::APPROVED_STATUSES, true)) {
            return 'success';
        }

        if (in_array($status, FireblocksStatusHelper::REJECTED_STATUSES, true)) {
            return 'danger';
        }

        if (in_array($status, FireblocksStatusHelper::CANCELLED_STATUSES, true)) {
            return 'warning';
        }

        return 'pending';
    }

    protected function toneForLiquidityStatus(string $status): string
    {
        return $this->toneForStatus($status);
    }

    protected function humanizeFireblocksStatus(string $status, ?string $subStatus = null): string
    {
        if ($subStatus) {
            return FireblocksStatusHelper::getSubstatusMessage($subStatus)
                ?: FireblocksStatusHelper::formatSubstatusDisplay($subStatus, false);
        }

        return FireblocksStatusHelper::primaryStatusLabel($status);
    }

    protected function formatAmount($amount, ?string $code, bool $isCrypto = false): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $formatted = rtrim(rtrim(number_format((float) $amount, $isCrypto ? 8 : 2, '.', ''), '0'), '.');

        return trim($formatted . ' ' . ($code ?? ''));
    }

    protected function formatFiat($amount, string $currency = 'USD'): string
    {
        return '$' . number_format((float) $amount, 2);
    }

    protected function extractAssetCode(?string $assetId): ?string
    {
        if (!$assetId) {
            return null;
        }

        return strtoupper(strtok($assetId, '_'));
    }

    protected function extractInstrumentBase(string $instrument): string
    {
        return strtoupper(strtok(str_replace(['/', '-'], ' ', $instrument), ' '));
    }

    protected function networkFromAsset(?string $assetId): ?string
    {
        if (!$assetId || !str_contains($assetId, '_')) {
            return null;
        }

        return ucwords(strtolower(str_replace('_', ' ', substr($assetId, strpos($assetId, '_') + 1))));
    }

    protected function formatVaultLabel(?string $vaultId, string $fallback): string
    {
        if (!$vaultId) {
            return $fallback;
        }

        return 'Vault account: ' . $vaultId;
    }

    protected function truncate(?string $value, int $length): ?string
    {
        if (!$value) {
            return null;
        }

        if (strlen($value) <= $length) {
            return $value;
        }

        return substr($value, 0, max(6, (int) ($length / 2))) . '…' . substr($value, -4);
    }
}
