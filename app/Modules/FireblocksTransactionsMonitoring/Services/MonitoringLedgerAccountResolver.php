<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\FireblocksTransaction;
use App\Models\TRRequest;
use Illuminate\Support\Collection;

/**
 * Resolves impacted client ledger account for Fireblocks monitoring.
 *
 * Canonical source: account_transaction_requests (TR) — every client crypto flow
 * creates a request first (account_id is always set). Fireblocks / liquidity ids
 * are stored on TR.crypto_transaction_id and mirrored on account_transactions.
 */
class MonitoringLedgerAccountResolver
{
    /** @var array<string, array<string, mixed>|null> */
    protected array $resolveCache = [];

    /**
     * @return array{
     *     account_id: int,
     *     title: string,
     *     account_number: string,
     *     account_label: string,
     *     ledger_status: ?string,
     *     account_transaction_id: ?int,
     *     tr_request_id: ?int,
     *     source: string
     * }|null
     */
    public function resolve(FireblocksTransaction $record): ?array
    {
        $cacheKey = (string) $record->fireblocks_tx_id;
        if (array_key_exists($cacheKey, $this->resolveCache)) {
            return $this->resolveCache[$cacheKey];
        }

        $trRequest = $this->findTransactionRequest($record);
        if ($trRequest && $trRequest->account_id) {
            $accountTransaction = $this->findAccountTransactionForRequest($record, $trRequest);
            $result = $this->fromTransactionRequest($trRequest, $accountTransaction);
            $this->resolveCache[$cacheKey] = $result;

            return $result;
        }

        $relatedTxIds = $this->relatedFireblocksTxIds($record);

        $transactions = AccountTransaction::query()
            ->whereIn('crypto_transaction_id', $relatedTxIds)
            ->orderByDesc('id')
            ->get();

        $picked = $this->pickLedgerTransaction($transactions, $record->direction);
        if ($picked && $picked->account_id) {
            $linkedTr = $picked->tr_request_id
                ? TRRequest::query()->find($picked->tr_request_id)
                : null;
            if ($linkedTr && $linkedTr->account_id) {
                $result = $this->fromTransactionRequest($linkedTr, $picked);
                $this->resolveCache[$cacheKey] = $result;

                return $result;
            }

            $result = $this->fromAccountId(
                (int) $picked->account_id,
                $picked->status,
                (int) $picked->id,
                $picked->tr_request_id ? (int) $picked->tr_request_id : null,
                'account_transaction'
            );
            $this->resolveCache[$cacheKey] = $result;

            return $result;
        }

        $this->resolveCache[$cacheKey] = null;

        return null;
    }

    /**
     * @param  object  $row  DataTables row (requires fireblocks_tx_id)
     */
    public function resolveForGridRow(object $row): ?array
    {
        $record = FireblocksTransaction::query()
            ->where('fireblocks_tx_id', $row->fireblocks_tx_id)
            ->first();

        return $record ? $this->resolve($record) : null;
    }

    public function formatLabelHtml(?array $info): string
    {
        if ($info === null || ($info['title'] ?? '') === '') {
            return '<span class="text-muted">—</span>';
        }

        return e($info['title']) . '<br><small class="text-muted">' . e($info['account_number']) . '</small>';
    }

    protected function findTransactionRequest(FireblocksTransaction $record): ?TRRequest
    {
        $relatedTxIds = $this->relatedFireblocksTxIds($record);

        $effectiveExternalTxId = $this->effectiveExternalTxId($record);
        if ($effectiveExternalTxId !== null) {
            $tr = TRRequest::query()->find((int) $effectiveExternalTxId);
            if ($tr && $tr->account_id) {
                return $tr;
            }
        }

        $tr = TRRequest::query()
            ->whereIn('crypto_transaction_id', $relatedTxIds)
            ->orderByDesc('id')
            ->first();

        if ($tr && $tr->account_id) {
            return $tr;
        }

        $trRequestId = AccountTransaction::query()
            ->whereIn('crypto_transaction_id', $relatedTxIds)
            ->whereNotNull('tr_request_id')
            ->orderByDesc('id')
            ->value('tr_request_id');

        if ($trRequestId) {
            $tr = TRRequest::query()->find($trRequestId);

            return ($tr && $tr->account_id) ? $tr : null;
        }

        return null;
    }

    protected function findAccountTransactionForRequest(FireblocksTransaction $record, TRRequest $trRequest): ?AccountTransaction
    {
        $relatedTxIds = $this->relatedFireblocksTxIds($record);

        $byCrypto = AccountTransaction::query()
            ->where('tr_request_id', $trRequest->id)
            ->whereIn('crypto_transaction_id', $relatedTxIds)
            ->orderByDesc('id')
            ->first();

        if ($byCrypto) {
            return $byCrypto;
        }

        return AccountTransaction::query()
            ->where('tr_request_id', $trRequest->id)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return list<string>
     */
    protected function relatedFireblocksTxIds(FireblocksTransaction $record): array
    {
        $ids = array_filter([
            trim((string) $record->fireblocks_tx_id),
            trim((string) ($record->parent_fireblocks_tx_id ?? '')),
            trim((string) ($record->root_fireblocks_tx_id ?? '')),
            trim((string) ($record->replaced_by_fireblocks_tx_id ?? '')),
        ], fn (string $id) => $id !== '');

        return array_values(array_unique($ids));
    }

    protected function effectiveExternalTxId(FireblocksTransaction $record): ?string
    {
        if ($this->isNumericExternalTxId($record->external_tx_id)) {
            return (string) (int) $record->external_tx_id;
        }

        $data = is_array($record->response_data) ? $record->response_data : [];
        foreach (['externalTxId', 'external_tx_id'] as $key) {
            if ($this->isNumericExternalTxId($data[$key] ?? null)) {
                return (string) (int) $data[$key];
            }
        }

        $bootstrap = $data['webhook_bootstrap'] ?? null;
        if (is_array($bootstrap) && $this->isNumericExternalTxId($bootstrap['externalTxId'] ?? null)) {
            return (string) (int) $bootstrap['externalTxId'];
        }

        return null;
    }

    /**
     * @return array{
     *     account_id: int,
     *     title: string,
     *     account_number: string,
     *     account_label: string,
     *     ledger_status: ?string,
     *     account_transaction_id: ?int,
     *     tr_request_id: ?int,
     *     source: string
     * }|null
     */
    protected function fromTransactionRequest(TRRequest $trRequest, ?AccountTransaction $accountTransaction = null): ?array
    {
        $account = Account::withoutGlobalScopes()->find($trRequest->account_id);
        if (!$account) {
            return null;
        }

        $title = (string) ($account->title ?? '');
        $number = (string) ($account->account_number ?? '');
        $ledgerStatus = $accountTransaction?->status ?? $trRequest->status;

        return [
            'account_id' => (int) $trRequest->account_id,
            'title' => $title,
            'account_number' => $number,
            'account_label' => trim($title . ' — ' . $number),
            'ledger_status' => $ledgerStatus,
            'account_transaction_id' => $accountTransaction ? (int) $accountTransaction->id : null,
            'tr_request_id' => (int) $trRequest->id,
            'source' => 'account_transaction_requests',
        ];
    }

    /**
     * @param  Collection<int, AccountTransaction>  $transactions
     */
    protected function pickLedgerTransaction(Collection $transactions, ?string $direction): ?AccountTransaction
    {
        if ($transactions->isEmpty()) {
            return null;
        }

        if ($transactions->count() === 1) {
            return $transactions->first();
        }

        $incoming = $this->isIncomingDirection($direction);

        $preferred = $transactions->first(function (AccountTransaction $tx) use ($incoming) {
            if ($incoming) {
                return strcasecmp((string) $tx->transaction_type, 'Credit') === 0
                    || strcasecmp((string) $tx->transaction_nature, 'Deposit') === 0;
            }

            return strcasecmp((string) $tx->transaction_type, 'Debit') === 0
                || strcasecmp((string) $tx->transaction_nature, 'Withdraw') === 0;
        });

        return $preferred ?: $transactions->sortByDesc('id')->first();
    }

    protected function isIncomingDirection(?string $direction): bool
    {
        return strtolower((string) $direction) === 'incoming';
    }

    protected function isNumericExternalTxId(mixed $externalTxId): bool
    {
        $value = trim((string) $externalTxId);

        return $value !== '' && ctype_digit($value);
    }

    /**
     * @return array{
     *     account_id: int,
     *     title: string,
     *     account_number: string,
     *     account_label: string,
     *     ledger_status: ?string,
     *     account_transaction_id: ?int,
     *     tr_request_id: ?int,
     *     source: string
     * }|null
     */
    protected function fromAccountId(
        int $accountId,
        ?string $ledgerStatus,
        ?int $accountTransactionId,
        ?int $trRequestId,
        string $source
    ): ?array {
        $account = Account::withoutGlobalScopes()->find($accountId);
        if (!$account) {
            return null;
        }

        $title = (string) ($account->title ?? '');
        $number = (string) ($account->account_number ?? '');

        return [
            'account_id' => $accountId,
            'title' => $title,
            'account_number' => $number,
            'account_label' => trim($title . ' — ' . $number),
            'ledger_status' => $ledgerStatus,
            'account_transaction_id' => $accountTransactionId,
            'tr_request_id' => $trRequestId,
            'source' => $source,
        ];
    }
}
