<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Models\FireblocksTransaction;
use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Modules\FireblocksTransactionsMonitoring\Models\FireblocksTransactionAlert;
use App\Modules\FireblocksTransactionsMonitoring\Support\MonitoringTransactionScope;
use Illuminate\Http\Request;

class MonitoringSummaryService
{
    public function buildFromRequest(Request $request): array
    {
        return $this->buildForScope(MonitoringTransactionScope::resolveLedgerScopeFromRequest($request));
    }

    /**
     * @return array{stuck: int, action_pending: int, open_alerts: int, pending_fb: int, completed: int, orphan: int}
     */
    public function build(?bool $showOrphansOnly = false): array
    {
        $scope = $showOrphansOnly ? 'orphan' : 'ledger';

        return $this->buildForScope($scope);
    }

    /**
     * @return array{stuck: int, action_pending: int, open_alerts: int, pending_fb: int, completed: int, orphan: int}
     */
    public function buildForScope(string $ledgerScope = 'ledger'): array
    {
        $query = FireblocksTransaction::query();
        MonitoringTransactionScope::applyLedgerScope($query, $ledgerScope);

        $orphanQuery = FireblocksTransaction::query();
        MonitoringTransactionScope::applyOrphansOnly($orphanQuery);

        $pendingStatuses = array_map('strtoupper', FireblocksStatusHelper::PENDING_STATUSES);

        return [
            'stuck' => (clone $query)->where('monitoring_state', 'stuck')->count(),
            'action_pending' => (clone $query)->where('monitoring_state', 'action_pending')->count(),
            'open_alerts' => FireblocksTransactionAlert::where('status', 'open')->count(),
            'pending_fb' => (clone $query)
                ->whereRaw(
                    'UPPER(status) IN (' . implode(',', array_fill(0, count($pendingStatuses), '?')) . ')',
                    $pendingStatuses
                )
                ->count(),
            'completed' => (clone $query)
                ->where(function ($q) {
                    $q->whereRaw('UPPER(status) IN (?, ?, ?)', ['COMPLETED', 'SUCCESS', 'PARTIALLY_COMPLETED'])
                        ->orWhere('internal_status', 'Approved');
                })
                ->count(),
            'orphan' => $orphanQuery->count(),
        ];
    }
}
