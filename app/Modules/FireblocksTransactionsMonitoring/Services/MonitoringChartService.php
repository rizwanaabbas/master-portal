<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Models\FireblocksTransaction;
use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Modules\FireblocksTransactionsMonitoring\Models\FireblocksTransactionMonitoringLog;
use App\Modules\FireblocksTransactionsMonitoring\Support\MonitoringTransactionScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MonitoringChartService
{
    private const TX_TABLE = 'fireblocks_transactions';

    /** @var array<string, string> */
    public const DIRECTION_COLORS = [
        'Incoming' => '#20c997',
        'Outgoing' => '#4e73df',
        'Internal' => '#6f42c1',
        'Orphan' => '#adb5bd',
    ];

    /** @var array<string, string> */
    public const PIPELINE_COLORS = [
        'Stuck' => '#dc3545',
        'Action pending' => '#ffc107',
        'In progress (FB)' => '#17a2b8',
        'Completed' => '#28a745',
    ];

    /** @var array<string, string> */
    public const STUCK_AGE_COLORS = [
        '0-15m' => '#28a745',
        '15-60m' => '#ffc107',
        '1-4h' => '#fd7e14',
        '4h+' => '#dc3545',
    ];

    /** @var array<string, string> */
    public const ACTION_COLORS = [
        'boost' => '#28a745',
        'drop' => '#ffc107',
        'resubmit' => '#6f42c1',
        'sync' => '#17a2b8',
    ];

    public function build(Request $request): array
    {
        $stuckQuery = $this->stuckAgeQuery($request);

        $stuckAgeRaw = [
            '0-15m' => (clone $stuckQuery)->where('stuck_detected_at', '>=', now()->subMinutes(15))->count(),
            '15-60m' => (clone $stuckQuery)->whereBetween('stuck_detected_at', [now()->subHour(), now()->subMinutes(15)])->count(),
            '1-4h' => (clone $stuckQuery)->whereBetween('stuck_detected_at', [now()->subHours(4), now()->subHour()])->count(),
            '4h+' => (clone $stuckQuery)->where('stuck_detected_at', '<', now()->subHours(4))->count(),
        ];

        $actionsRaw = FireblocksTransactionMonitoringLog::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->whereIn('action', ['boost', 'drop', 'resubmit', 'sync'])
            ->selectRaw('action, COUNT(*) as total')
            ->groupBy('action')
            ->pluck('total', 'action')
            ->all();

        $scoped = $this->filteredChartBaseQuery($request);

        return [
            'direction_orphan' => $this->buildDirectionOrphanSeries($request),
            'pipeline' => $this->buildPipelineSeries(clone $scoped),
            'stuck_age' => $this->buildStuckAgeSeries($stuckAgeRaw),
            'actions_last_7' => $this->buildActionSeries($actionsRaw),
            'scope_total' => (clone $scoped)->count(),
            'stuck_total' => (clone $stuckQuery)->count(),
            'orphan_total' => $this->orphanCount($request),
        ];
    }

    protected function orphanCount(Request $request): int
    {
        $query = $this->filteredChartBaseQuery($request);
        MonitoringTransactionScope::applyOrphansOnly($query);

        return $query->count();
    }

    /**
     * Incoming / outgoing / internal + orphan custody bucket (matches grid filters).
     *
     * @return array{labels: array<int, string>, values: array<int, int>, colors: array<int, string>, center_total: int}
     */
    protected function buildDirectionOrphanSeries(Request $request): array
    {
        $scope = MonitoringTransactionScope::resolveLedgerScopeFromRequest($request);
        $base = $this->filteredChartBaseQuery($request);

        if ($scope === 'orphan') {
            return $this->directionSeriesFromQuery($base, includeOrphanSlice: false);
        }

        if ($scope === 'ledger' || $scope === 'poisoning') {
            return $this->directionSeriesFromQuery($base, includeOrphanSlice: false);
        }

        $orphanCount = (clone $base)->tap(fn (Builder $q) => MonitoringTransactionScope::applyOrphansOnly($q))->count();
        $ledger = clone $base;
        MonitoringTransactionScope::applyExcludeOrphans($ledger);

        $series = $this->directionCountsFromQuery($ledger);
        $labels = ['Incoming', 'Outgoing', 'Internal', 'Orphan'];
        $values = [$series['incoming'], $series['outgoing'], $series['internal'], $orphanCount];
        $colors = array_map(fn (string $label) => self::DIRECTION_COLORS[$label], $labels);

        return [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colors,
            'center_total' => array_sum($values),
        ];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>, colors: array<int, string>, center_total: int}
     */
    protected function directionSeriesFromQuery(Builder $query, bool $includeOrphanSlice): array
    {
        $counts = $this->directionCountsFromQuery($query);
        $labels = ['Incoming', 'Outgoing', 'Internal'];
        $values = [$counts['incoming'], $counts['outgoing'], $counts['internal']];
        $colors = array_map(fn (string $label) => self::DIRECTION_COLORS[$label], $labels);

        if ($includeOrphanSlice) {
            $labels[] = 'Orphan';
            $values[] = 0;
            $colors[] = self::DIRECTION_COLORS['Orphan'];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colors,
            'center_total' => array_sum($values),
        ];
    }

    /**
     * @return array{incoming: int, outgoing: int, internal: int}
     */
    protected function directionCountsFromQuery(Builder $query): array
    {
        $incoming = (clone $query)->where(self::TX_TABLE . '.direction', 'incoming')->count();
        $outgoing = (clone $query)->where(self::TX_TABLE . '.direction', 'outgoing')->count();
        $internal = (clone $query)->where(self::TX_TABLE . '.direction', 'internal')->count();
        $unclassified = (clone $query)->where(function (Builder $q) {
            $q->whereNull(self::TX_TABLE . '.direction')
                ->orWhere(self::TX_TABLE . '.direction', '')
                ->orWhereNotIn(self::TX_TABLE . '.direction', ['incoming', 'outgoing', 'internal']);
        })->count();

        return [
            'incoming' => $incoming,
            'outgoing' => $outgoing + $unclassified,
            'internal' => $internal,
        ];
    }

    /**
     * Same filter semantics as the monitoring grid (including ledger scope + account).
     */
    protected function filteredChartBaseQuery(Request $request): Builder
    {
        $scope = MonitoringTransactionScope::resolveLedgerScopeFromRequest($request);

        $query = FireblocksTransaction::query()->select(self::TX_TABLE . '.*');

        if (MonitoringTransactionScope::scopeRequiresLedgerAccount($scope)) {
            MonitoringTransactionScope::applyLedgerAccountJoins($query);
        }

        $this->applyDashboardFilters($query, $request);

        MonitoringTransactionScope::applyTransactionScope(
            $query,
            $scope,
            MonitoringConfigService::tryGet(),
        );

        if (MonitoringTransactionScope::scopeRequiresLedgerAccount($scope)) {
            MonitoringTransactionScope::applyLedgerAccountPresent($query);
        }

        return $query;
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>, colors: array<int, string>}
     */
    protected function buildPipelineSeries(Builder $ledgerScope): array
    {
        $pendingStatuses = array_map('strtoupper', FireblocksStatusHelper::PENDING_STATUSES);
        $placeholders = implode(',', array_fill(0, count($pendingStatuses), '?'));

        $stuck = (clone $ledgerScope)->where(self::TX_TABLE . '.monitoring_state', 'stuck')->count();
        $actionPending = (clone $ledgerScope)->where(self::TX_TABLE . '.monitoring_state', 'action_pending')->count();
        $inProgress = (clone $ledgerScope)
            ->whereRaw('UPPER(' . self::TX_TABLE . '.status) IN (' . $placeholders . ')', $pendingStatuses)
            ->count();
        $completed = (clone $ledgerScope)
            ->where(function (Builder $q) {
                $q->whereRaw('UPPER(' . self::TX_TABLE . '.status) IN (?, ?, ?)', ['COMPLETED', 'SUCCESS', 'PARTIALLY_COMPLETED'])
                    ->orWhere(self::TX_TABLE . '.internal_status', 'Approved');
            })
            ->count();

        $labels = ['Stuck', 'Action pending', 'In progress (FB)', 'Completed'];
        $values = [$stuck, $actionPending, $inProgress, $completed];
        $colors = array_map(fn (string $label) => self::PIPELINE_COLORS[$label], $labels);

        return compact('labels', 'values', 'colors');
    }

    protected function stuckAgeQuery(Request $request): Builder
    {
        $query = $this->filteredChartBaseQuery($request)
            ->where(self::TX_TABLE . '.monitoring_state', 'stuck')
            ->whereNotNull(self::TX_TABLE . '.stuck_detected_at');

        return $query;
    }

    protected function applyDashboardFilters(Builder $query, Request $request): void
    {
        if ($request->filled('monitoring_state') && $request->monitoring_state !== 'all') {
            $query->where(self::TX_TABLE . '.monitoring_state', $request->monitoring_state);
        }

        if ($request->filled('fb_status') && $request->fb_status !== 'all') {
            $query->where(self::TX_TABLE . '.status', strtoupper($request->fb_status));
        }

        if ($request->filled('fb_sub_status') && $request->fb_sub_status !== 'all') {
            $matchCodes = FireblocksStatusHelper::substatusFilterMatchCodes($request->fb_sub_status);
            $query->whereIn(self::TX_TABLE . '.sub_status', $matchCodes);
        }

        if ($request->filled('internal_status') && $request->internal_status !== 'all') {
            FireblocksStatusHelper::applyInternalStatusFilter($query, (string) $request->internal_status, self::TX_TABLE);
        }

        if ($request->filled('direction') && $request->direction !== 'all') {
            $query->where(self::TX_TABLE . '.direction', $request->direction);
        }

        if ($request->boolean('stuck_only')) {
            $query->where(self::TX_TABLE . '.monitoring_state', 'stuck');
        }
    }

    /**
     * @param  array<string, int|string>  $raw
     * @return array{labels: array<int, string>, values: array<int, int>, colors: array<int, string>}
     */
    protected function buildStuckAgeSeries(array $raw): array
    {
        $labels = [];
        $values = [];
        $colors = [];

        foreach (['0-15m', '15-60m', '1-4h', '4h+'] as $bucket) {
            $labels[] = $bucket;
            $values[] = (int) ($raw[$bucket] ?? 0);
            $colors[] = self::STUCK_AGE_COLORS[$bucket];
        }

        return compact('labels', 'values', 'colors');
    }

    /**
     * @param  array<string, int|string>  $raw
     * @return array{labels: array<int, string>, values: array<int, int>, colors: array<int, string>}
     */
    protected function buildActionSeries(array $raw): array
    {
        $labels = [];
        $values = [];
        $colors = [];

        foreach (['boost', 'drop', 'resubmit', 'sync'] as $action) {
            $labels[] = ucfirst($action);
            $values[] = (int) ($raw[$action] ?? 0);
            $colors[] = self::ACTION_COLORS[$action];
        }

        return compact('labels', 'values', 'colors');
    }
}
