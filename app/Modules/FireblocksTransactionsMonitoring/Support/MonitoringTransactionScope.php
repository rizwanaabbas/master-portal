<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Support;

use App\Models\FireblocksTransaction;
use Illuminate\Database\Eloquent\Builder;

class MonitoringTransactionScope
{
    public static function isOrphanCustody(FireblocksTransaction $record): bool
    {
        return FireblocksTransaction::isCustodyOnlyWithoutLedger($record);
    }

    /**
     * Hide orphan / custody-only deposits (no client ledger link).
     */
    public static function applyExcludeOrphans(Builder $query, string $table = 'fireblocks_transactions'): void
    {
        $noteCol = $table . '.note';

        $query->where(function (Builder $q) use ($noteCol) {
            $q->whereNull($noteCol)
                ->orWhere($noteCol, '')
                ->orWhere(function (Builder $inner) use ($noteCol) {
                    $inner->where($noteCol, 'not like', 'Custody only — deposit address not in ledger%')
                        ->where($noteCol, '!=', FireblocksTransaction::CUSTODY_ONLY_NO_LEDGER_NOTE)
                        ->where($noteCol, '!=', FireblocksTransaction::CUSTODY_ONLY_SWEEP_GAS_FEED_NOTE)
                        ->where($noteCol, 'not like', 'Custody only — TRON Station gas feed%');
                });
        });
    }

    public static function applyOrphansOnly(Builder $query, string $table = 'fireblocks_transactions'): void
    {
        $noteCol = $table . '.note';

        $query->where(function (Builder $q) use ($noteCol) {
            $q->where($noteCol, 'like', 'Custody only — deposit address not in ledger%')
                ->orWhere($noteCol, FireblocksTransaction::CUSTODY_ONLY_NO_LEDGER_NOTE)
                ->orWhere($noteCol, FireblocksTransaction::CUSTODY_ONLY_SWEEP_GAS_FEED_NOTE)
                ->orWhere($noteCol, 'like', 'Custody only — TRON Station gas feed%');
        });
    }

    public static function applyOrphanFilter(Builder $query, ?bool $showOrphansOnly, string $table = 'fireblocks_transactions'): void
    {
        if ($showOrphansOnly === true) {
            self::applyOrphansOnly($query, $table);

            return;
        }

        if ($showOrphansOnly === false) {
            self::applyExcludeOrphans($query, $table);
        }
    }

    /**
     * @param  'ledger'|'all'|'orphan'|'poisoning'  $scope
     */
    public static function applyLedgerScope(Builder $query, string $scope, string $table = 'fireblocks_transactions'): void
    {
        $scope = strtolower(trim($scope));

        if ($scope === 'orphan') {
            self::applyOrphansOnly($query, $table);

            return;
        }

        if ($scope === 'all') {
            return;
        }

        if ($scope === 'poisoning') {
            return;
        }

        self::applyExcludeOrphans($query, $table);
    }

    /**
     * Apply transaction scope including poisoning-deposit heuristics (requires config for poisoning).
     *
     * @param  'ledger'|'all'|'orphan'|'poisoning'  $scope
     * @param  array<string, mixed>|null  $monitoringConfig
     */
    public static function applyTransactionScope(
        Builder $query,
        string $scope,
        ?array $monitoringConfig = null,
        string $table = 'fireblocks_transactions',
    ): void {
        $scope = strtolower(trim($scope));

        if ($scope === 'poisoning') {
            PoisoningDepositScope::apply($query, PoisoningDepositScope::resolveAmountLimits($monitoringConfig), $table);

            return;
        }

        self::applyLedgerScope($query, $scope, $table);
    }

    /**
     * Requires account join aliases from TransactionMonitoringController::baseGridQuery().
     */
    public static function applyLedgerAccountPresent(Builder $query): void
    {
        $query->whereRaw('COALESCE(acc_tr_ext.id, acc_tr_crypto.id, acc_tr_at.id, acc_at.id) IS NOT NULL');
    }

    /**
     * Join paths used to resolve client account (grid + chart filters for ledger scope).
     */
    public static function applyLedgerAccountJoins(Builder $query, string $table = 'fireblocks_transactions'): void
    {
        $collation = 'utf8mb4_0900_ai_ci';
        $txIdCol = $table . '.fireblocks_tx_id';
        $externalTxCol = $table . '.external_tx_id';

        $query->leftJoin('account_transactions as at', function ($join) use ($collation, $txIdCol) {
            $join->whereRaw(
                "at.crypto_transaction_id COLLATE {$collation} = {$txIdCol} COLLATE {$collation}"
            );
        })
            ->leftJoin('account_transaction_requests as tr_at', 'tr_at.id', '=', 'at.tr_request_id')
            ->leftJoin('account_transaction_requests as tr_crypto', function ($join) use ($collation, $txIdCol) {
                $join->whereRaw(
                    "tr_crypto.crypto_transaction_id COLLATE {$collation} = {$txIdCol} COLLATE {$collation}"
                );
            })
            ->leftJoin('account_transaction_requests as tr_ext', function ($join) use ($externalTxCol) {
                $join->whereRaw("CAST(tr_ext.id AS CHAR) = {$externalTxCol}")
                    ->whereRaw("{$externalTxCol} REGEXP '^[0-9]+$'");
            })
            ->leftJoin('accounts as acc_at', 'acc_at.id', '=', 'at.account_id')
            ->leftJoin('accounts as acc_tr_at', 'acc_tr_at.id', '=', 'tr_at.account_id')
            ->leftJoin('accounts as acc_tr_crypto', 'acc_tr_crypto.id', '=', 'tr_crypto.account_id')
            ->leftJoin('accounts as acc_tr_ext', 'acc_tr_ext.id', '=', 'tr_ext.account_id');
    }

    public static function resolveLedgerScopeFromRequest(\Illuminate\Http\Request $request): string
    {
        if ($request->boolean('show_orphans')) {
            return 'orphan';
        }

        $scope = strtolower(trim((string) $request->input('ledger_scope', 'ledger')));

        return in_array($scope, ['ledger', 'all', 'orphan', 'poisoning'], true) ? $scope : 'ledger';
    }

    public static function scopeRequiresLedgerAccount(string $scope): bool
    {
        return in_array(strtolower(trim($scope)), ['ledger', 'poisoning'], true);
    }
}
