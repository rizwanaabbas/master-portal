<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Controllers;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\FireblocksTransaction;
use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Modules\Fireblocks\Support\FireblocksAmount;
use App\Modules\FireblocksTransactionsMonitoring\Models\FireblocksTransactionAlert;
use App\Modules\FireblocksTransactionsMonitoring\Services\ChainalysisComplianceSyncService;
use App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringChartService;
use App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringConfigService;
use App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringEnabledCryptoSnapshotService;
use App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringSummaryService;
use App\Modules\FireblocksTransactionsMonitoring\Support\MonitoringDashboardUiHints;
use App\Modules\FireblocksTransactionsMonitoring\Support\MonitoringTransactionScope;
use App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringQueueStatusService;
use App\Modules\FireblocksTransactionsMonitoring\Services\TransactionActionEligibilityService;
use App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringLedgerAccountResolver;
use App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringTransactionDetailService;
use App\Modules\FireblocksTransactionsMonitoring\Services\TransactionActionService;
use App\Modules\FireblocksTransactionsMonitoring\Services\TransactionRetryChainService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class TransactionMonitoringController extends Controller
{
    public function __construct(
        protected TransactionActionService $actionService,
        protected MonitoringQueueStatusService $queueStatusService,
        protected TransactionActionEligibilityService $eligibilityService,
        protected TransactionRetryChainService $retryChainService,
        protected MonitoringChartService $chartService,
        protected MonitoringTransactionDetailService $detailService,
        protected MonitoringLedgerAccountResolver $ledgerAccountResolver,
        protected MonitoringSummaryService $summaryService,
    ) {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:view fireblocks transaction monitoring', [
            'only' => ['index', 'manual', 'data', 'charts', 'summary', 'statusReference', 'show'],
        ]);
        $this->middleware('permission:view fireblocks transaction monitoring|view transaction monitoring currency rates', [
            'only' => ['currencyRates', 'syncCurrencyRates'],
        ]);
        $this->middleware('permission:view fireblocks transaction monitoring|view transaction monitoring fireblocks balances', [
            'only' => ['fireblocksBalances', 'syncFireblocksBalances'],
        ]);
        $this->middleware('permission:view fireblocks transaction monitoring|view transaction monitoring aquanow balances', [
            'only' => ['aquanowBalances', 'syncAquanowBalances'],
        ]);
        $this->middleware('permission:manage fireblocks transaction monitoring', [
            'only' => ['sync', 'boost', 'drop', 'resubmit', 'approveAlert', 'dismissAlert'],
        ]);
    }

    public function index(Request $request)
    {
        $config = MonitoringConfigService::tryGet();
        $pageSize = $config['dashboard_page_size'] ?? 15;
        $defaultFilters = MonitoringConfigService::dashboardDefaultFilters($config);

        $summary = $this->summaryService->buildForScope('ledger');

        $substatusFilterOptions = FireblocksStatusHelper::substatusFilterOptions();
        $primaryStatusFilterOptions = FireblocksStatusHelper::primaryStatusFilterOptions();
        $internalStatusFilterOptions = FireblocksStatusHelper::internalStatusFilterOptions();
        $statusCatalog = FireblocksStatusHelper::statusReferenceCatalog($config);

        $chainalysisStatusColumnReady = Schema::hasColumn('fireblocks_transactions', 'chainalysis_status');
        $dashboardColumns = MonitoringConfigService::dashboardColumnVisibility($config);
        $uiHints = MonitoringDashboardUiHints::all();
        $enabledModules = array_map('strval', Common::getEnabledModules());
        $moduleOn = function (string $name) use ($enabledModules): bool {
            foreach ($enabledModules as $enabled) {
                if (strcasecmp($enabled, $name) === 0) {
                    return true;
                }
            }

            return false;
        };
        $canViewMonitoring = auth()->user()->can('view fireblocks transaction monitoring');
        $showCurrencyRates = $moduleOn('CoinGecko')
            && ($canViewMonitoring || auth()->user()->can('view transaction monitoring currency rates'));
        $showFbBalances = $moduleOn('Fireblocks')
            && ($canViewMonitoring || auth()->user()->can('view transaction monitoring fireblocks balances'));
        $showAquanowBalances = $moduleOn('Aquanow')
            && ($canViewMonitoring || auth()->user()->can('view transaction monitoring aquanow balances'));
        $showAquanowBuySell = MonitoringConfigService::includeAquanowBuySell($config);

        return view('fireblocks-monitoring::index', compact(
            'pageSize',
            'summary',
            'config',
            'defaultFilters',
            'substatusFilterOptions',
            'primaryStatusFilterOptions',
            'internalStatusFilterOptions',
            'statusCatalog',
            'chainalysisStatusColumnReady',
            'dashboardColumns',
            'uiHints',
            'showCurrencyRates',
            'showFbBalances',
            'showAquanowBalances',
            'showAquanowBuySell',
        ));
    }

    public function manual()
    {
        return view('fireblocks-monitoring::ops-manual');
    }

    public function data(Request $request)
    {
        $includeAquanow = MonitoringConfigService::includeAquanowBuySell();
        $provider = strtolower((string) $request->input('provider', 'all'));
        if ($includeAquanow && in_array($provider, ['all', 'aquanow'], true)) {
            return $this->combinedMonitoringData($request, $provider);
        }

        $query = $this->baseGridQuery();
        $this->applyDashboardFilters($query, $request);
        $chainalysisStatusColumnReady = Schema::hasColumn('fireblocks_transactions', 'chainalysis_status');

        $dataTable = DataTables::of($query)
            ->filter(function ($q) use ($request, $chainalysisStatusColumnReady) {
                if ($search = $request->input('search.value')) {
                    $q->where(function ($inner) use ($search, $chainalysisStatusColumnReady) {
                        $inner->where('fireblocks_transactions.fireblocks_tx_id', 'like', "%{$search}%")
                            ->orWhere('fireblocks_transactions.asset_id', 'like', "%{$search}%")
                            ->orWhere('fireblocks_transactions.tx_hash', 'like', "%{$search}%")
                            ->orWhere('fireblocks_transactions.destination_address', 'like', "%{$search}%")
                            ->orWhere('acc_tr_ext.title', 'like', "%{$search}%")
                            ->orWhere('acc_tr_ext.account_number', 'like', "%{$search}%")
                            ->orWhere('acc_tr_crypto.title', 'like', "%{$search}%")
                            ->orWhere('acc_tr_crypto.account_number', 'like', "%{$search}%")
                            ->orWhere('acc_tr_at.title', 'like', "%{$search}%")
                            ->orWhere('acc_tr_at.account_number', 'like', "%{$search}%")
                            ->orWhere('acc_at.title', 'like', "%{$search}%")
                            ->orWhere('acc_at.account_number', 'like', "%{$search}%");
                        if ($chainalysisStatusColumnReady) {
                            $inner->orWhere('fireblocks_transactions.chainalysis_status', 'like', "%{$search}%");
                        }
                    });
                }
            }, true)
            ->addColumn('provider_badge', fn () => '<span class="badge badge-secondary">Fireblocks</span>')
            ->addColumn('fb_status_badge', function ($row) {
                $statusCode = FireblocksStatusHelper::resolveStoredPrimaryStatus($row);
                if ($statusCode === '') {
                    $statusCode = strtoupper(trim((string) ($row->status ?? '')));
                }
                $badge = FireblocksStatusHelper::primaryStatusBadge($statusCode);

                return '<span class="badge ' . e($badge['class']) . '" title="' . e($statusCode) . '">'
                    . e($badge['label']) . '</span>';
            })
            ->addColumn('internal_status_badge', function ($row) {
                $resolved = FireblocksStatusHelper::resolveForMonitoring($row);
                $badge = FireblocksStatusHelper::internalStatusBadge($resolved['internal_status']);
                $title = $resolved['message'] ?? '';

                return '<span class="badge ' . $badge['class'] . '" title="' . e($title) . '">' . e($badge['label']) . '</span>';
            })
            ->addColumn('monitoring_badge', function ($row) {
                $badge = FireblocksStatusHelper::monitoringStateBadge($row->monitoring_state ?: 'normal');

                return '<span class="badge ' . $badge['class'] . '">' . e($badge['label']) . '</span>';
            })
            ->addColumn('chainalysis_badge', fn ($row) => app(ChainalysisComplianceSyncService::class)->gridBadgeHtml($row))
            ->addColumn('queue_status_badge', fn ($row) => $this->queueStatusService->badgeHtml($row))
            ->addColumn('retry_chain_badge', function ($row) {
                if (!$row instanceof FireblocksTransaction) {
                    $record = FireblocksTransaction::where('fireblocks_tx_id', $row->fireblocks_tx_id)->first();
                    if (!$record) {
                        return '<span class="text-muted">—</span>';
                    }

                    return $this->retryChainService->gridBadgeHtml($record);
                }

                return $this->retryChainService->gridBadgeHtml($row);
            })
            ->addColumn('reattempt_count', fn ($row) => (int) ($row->auto_action_count ?? 0))
            ->addColumn('last_reattempt_fmt', function ($row) {
                if (!$row->last_reattempt_at) {
                    return '<span class="text-muted">—</span>';
                }

                return e($row->last_reattempt_at->format('Y-m-d H:i:s'));
            })
            ->addColumn('stuck_for', function ($row) {
                if (!$row->stuck_detected_at) {
                    return '—';
                }

                return e($row->stuck_detected_at->diffForHumans(null, true));
            })
            ->addColumn('account_label', function ($row) {
                $info = $this->ledgerAccountResolver->resolveForGridRow($row);

                return $this->ledgerAccountResolver->formatLabelHtml($info);
            })
            ->addColumn('amount_fmt', function ($row) {
                return FireblocksAmount::formatHtml($row);
            })
            ->addColumn('ledger_status_badge', function ($row) {
                $info = $this->ledgerAccountResolver->resolveForGridRow($row);
                $status = $info['ledger_status'] ?? $row->ledger_status ?? null;
                if (empty($status)) {
                    return '<span class="text-muted" title="No linked account_transactions / TR request">—</span>';
                }

                return '<span class="badge badge-secondary" title="Ledger posting status (account_transactions / request)">' . e($status) . '</span>';
            })
            ->addColumn('actions', function ($row) {
                $txId = e($row->fireblocks_tx_id);
                $ledgerInfo = $this->ledgerAccountResolver->resolveForGridRow($row);
                $ledgerUrl = '';
                if (!empty($ledgerInfo['account_transaction_id'])) {
                    $ledgerUrl = url('/crypto-management/crypto-transactions/edit/crypto-transaction/' . $ledgerInfo['account_transaction_id']);
                } elseif (!empty($ledgerInfo['tr_request_id'])) {
                    $ledgerUrl = url('/crypto-management/crypto-pending-transactions/view/crypto-transaction/' . $ledgerInfo['tr_request_id']);
                } elseif (!empty($row->account_transaction_id)) {
                    $ledgerUrl = url('/crypto-management/crypto-transactions/edit/crypto-transaction/' . $row->account_transaction_id);
                }

                $html = '<div class="btn-group btn-group-sm" role="group">';
                $html .= '<button type="button" class="btn btn-outline-primary btn-view-tx" data-tx-id="' . $txId . '" title="Quick view"><i class="fal fa-eye"></i></button>';
                if ($ledgerUrl) {
                    $html .= '<a href="' . $ledgerUrl . '" target="_blank" class="btn btn-outline-secondary" title="Open ledger record"><i class="fal fa-external-link"></i></a>';
                }
                $html .= '</div>';

                return $html;
            })
            ->editColumn('id', function ($row) {
                return $this->formatTrRequestIdCell($row->tr_request_id ?? null);
            })
            ->editColumn('direction', function ($row) {
                $direction = trim((string) ($row->direction ?? ''));

                return $direction !== '' ? e(ucfirst($direction)) : '<span class="text-muted">—</span>';
            })
            ->editColumn('sub_status', function ($row) {
                $subStatus = FireblocksStatusHelper::resolveStoredSubStatus($row);
                if ($subStatus === null || $subStatus === '') {
                    if (FireblocksStatusHelper::isFailedSubmissionPending($row) && !empty($row->pending_reason)) {
                        return '<span class="text-warning small">' . e($row->pending_reason) . '</span>';
                    }

                    return '—';
                }
                $msg = FireblocksStatusHelper::getSubstatusMessage($subStatus) ?: $subStatus;
                $category = FireblocksStatusHelper::substatusCategory($subStatus);
                $resolved = FireblocksStatusHelper::resolveForMonitoring($row);
                $title = $subStatus . ' → ' . $resolved['internal_status'];
                if ($category !== '' && $category !== 'Other') {
                    $title = $category . ' | ' . $title;
                }

                $subBadge = FireblocksStatusHelper::substatusBadge($subStatus);
                $html = '<span title="' . e($title) . '"><span class="' . e($subBadge['class']) . ' mr-1">'
                    . e($subBadge['label']) . '</span>' . e($msg) . '</span>';
                if (FireblocksStatusHelper::isCustodyBalancePending($row) && !empty($row->pending_reason)) {
                    $html .= '<div class="text-warning small mt-1">' . e($row->pending_reason) . '</div>';
                }

                return $html;
            })
            ->editColumn('created_at', fn ($row) => $row->created_at?->format('Y-m-d H:i:s') ?? '—')
            ->editColumn('updated_at', fn ($row) => $row->updated_at?->format('Y-m-d H:i:s') ?? '—')
            ->setRowClass(function ($row) {
                if (($row->monitoring_state ?? '') === 'stuck') {
                    return 'fb-row-stuck';
                }
                if (($row->monitoring_state ?? '') === 'action_pending') {
                    return 'fb-row-action-pending';
                }

                return '';
            })
            ->rawColumns([
                'id',
                'direction',
                'provider_badge',
                'fb_status_badge',
                'internal_status_badge',
                'monitoring_badge',
                'chainalysis_badge',
                'queue_status_badge',
                'retry_chain_badge',
                'last_reattempt_fmt',
                'account_label',
                'amount_fmt',
                'ledger_status_badge',
                'actions',
                'sub_status',
            ]);

        $dataTable->orderColumn('fireblocks_transactions.id', function ($query, $order) {
            $this->applyTrRequestIdGridOrder($query, $order);
        });
        $dataTable->orderColumn('fireblocks_transactions.fireblocks_tx_id', function ($query, $order) {
            $this->applyGridOrder($query, 'fireblocks_transactions.fireblocks_tx_id', $order);
        });
        $dataTable->orderColumn('fireblocks_transactions.direction', function ($query, $order) {
            $this->applyGridOrder($query, 'fireblocks_transactions.direction', $order);
        });
        $dataTable->orderColumn('fireblocks_transactions.created_at', function ($query, $order) {
            $this->applyGridOrder($query, 'fireblocks_transactions.created_at', $order);
        });
        $dataTable->orderColumn('fireblocks_transactions.updated_at', function ($query, $order) {
            $this->applyGridOrder($query, 'fireblocks_transactions.updated_at', $order);
        });
        $dataTable->orderColumn('fireblocks_transactions.auto_action_count', function ($query, $order) {
            $this->applyGridOrder($query, 'fireblocks_transactions.auto_action_count', $order);
        });
        $dataTable->orderColumn('fireblocks_transactions.last_reattempt_at', function ($query, $order) {
            $this->applyGridOrder($query, 'fireblocks_transactions.last_reattempt_at', $order);
        });
        $dataTable->orderColumn('fireblocks_transactions.stuck_detected_at', function ($query, $order) {
            $this->applyGridOrder($query, 'fireblocks_transactions.stuck_detected_at', $order);
        });
        $dataTable->orderColumn('fireblocks_transactions.chainalysis_status', function ($query, $order) use ($chainalysisStatusColumnReady) {
            if ($chainalysisStatusColumnReady) {
                $this->applyGridOrder($query, 'fireblocks_transactions.chainalysis_status', $order);
            } else {
                $this->applyGridOrder($query, 'fireblocks_transactions.updated_at', 'desc');
            }
        });

        return $dataTable->make(true);
    }

    public function charts(Request $request)
    {
        $payload = $this->chartService->build($request);

        return response()->json(array_merge(['success' => true], $payload));
    }

    public function summary(Request $request)
    {
        return response()->json([
            'success' => true,
            'summary' => $this->summaryService->buildFromRequest($request),
        ]);
    }

    public function currencyRates(MonitoringEnabledCryptoSnapshotService $snapshot)
    {
        abort_unless(Common::isModuleEnabled('CoinGecko'), 404);

        return response()->json([
            'success' => true,
            'rows' => $snapshot->currencyRates(),
        ] + $snapshot->currencyRatesMeta());
    }

    public function syncCurrencyRates(MonitoringEnabledCryptoSnapshotService $snapshot)
    {
        abort_unless(Common::isModuleEnabled('CoinGecko'), 404);
        \App\Modules\Fireblocks\Support\CoinGeckoUsdPriceService::refreshCachedPrices();

        return $this->currencyRates($snapshot);
    }

    public function fireblocksBalances(Request $request, MonitoringEnabledCryptoSnapshotService $snapshot)
    {
        abort_unless(Common::isModuleEnabled('Fireblocks'), 404);
        $source = $request->input('source') === 'omnibus' ? 'omnibus' : 'wallet_pool';

        return response()->json([
            'success' => true,
            'source' => $source,
            'rows' => $snapshot->fireblocksBalances($source),
        ] + $snapshot->fireblocksBalancesMeta($source));
    }

    public function syncFireblocksBalances(
        Request $request,
        MonitoringEnabledCryptoSnapshotService $snapshot,
        \App\Modules\Fireblocks\Services\WithdrawalPoolBalanceSyncService $sync
    ) {
        abort_unless(Common::isModuleEnabled('Fireblocks'), 404);
        set_time_limit(300);
        $sync->sync();

        return $this->fireblocksBalances($request, $snapshot);
    }

    public function aquanowBalances(MonitoringEnabledCryptoSnapshotService $snapshot)
    {
        abort_unless(Common::isModuleEnabled('Aquanow'), 404);

        return response()->json([
            'success' => true,
            'rows' => $snapshot->aquanowBalances(),
        ] + $snapshot->aquanowBalancesMeta());
    }

    public function syncAquanowBalances(
        MonitoringEnabledCryptoSnapshotService $snapshot,
        \App\Modules\Aquanow\Services\BalanceService $balances
    ) {
        abort_unless(Common::isModuleEnabled('Aquanow'), 404);
        set_time_limit(120);
        $balances->getBalances();

        return $this->aquanowBalances($snapshot);
    }

    protected function isAquanowGridRow(object $row): bool
    {
        return ($row->grid_source ?? '') === 'aquanow';
    }

    protected function aquanowGridQuery(Request $request)
    {
        $query = DB::table('liquidity_orders')
            ->where('provider', 'aquanow')
            ->whereIn('side', ['buy', 'sell'])
            ->selectRaw("
                liquidity_orders.tr_request_id as id,
                liquidity_orders.id as source_id,
                'aquanow' as grid_source,
                COALESCE(liquidity_orders.order_id, liquidity_orders.client_order_id) as fireblocks_tx_id,
                liquidity_orders.side as direction,
                liquidity_orders.status as status,
                liquidity_orders.created_at as created_at,
                liquidity_orders.updated_at as updated_at,
                liquidity_orders.cr_tr_id as cr_tr_id,
                liquidity_orders.tr_request_id as tr_request_id,
                liquidity_orders.instrument as instrument,
                liquidity_orders.quantity as quantity,
                liquidity_orders.price as price,
                COALESCE(liquidity_orders.grand_total, liquidity_orders.sub_total, 0) as grand_total,
                liquidity_orders.side as side,
                liquidity_orders.status as internal_status,
                'normal' as monitoring_state,
                NULL as sub_status
            ");

        // Direction All = Fireblocks directions + Aquanow buy and sell (both sides kept).
        $direction = strtolower(trim((string) $request->input('direction', 'all')));
        if ($direction === '' || $direction === 'all') {
            $query->whereIn('side', ['buy', 'sell']);
        } elseif ($direction === 'incoming' || $direction === 'buy') {
            $query->where('side', 'buy');
        } elseif ($direction === 'outgoing' || $direction === 'sell') {
            $query->where('side', 'sell');
        } elseif ($direction === 'internal') {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('side', ['buy', 'sell']);
        }

        // Only apply Status when it can match Aquanow order statuses. Fireblocks-only
        // codes (COMPLETED, SUBMITTED, …) must not hide buy/sell from Direction=All.
        $status = strtoupper(trim((string) $request->input('fb_status', 'all')));
        $aquanowStatuses = ['APPROVED', 'PENDING', 'FAILED', 'QUOTED', 'EXECUTED'];
        if ($status !== '' && $status !== 'ALL' && in_array($status, $aquanowStatuses, true)) {
            $query->whereRaw('UPPER(liquidity_orders.status) = ?', [$status]);
        }

        $monitoringState = (string) $request->input('monitoring_state', 'all');
        if ($request->boolean('stuck_only') || ($monitoringState !== 'all' && $monitoringState !== 'normal')) {
            $query->whereRaw('1 = 0');
        }

        if ($search = $request->input('search.value')) {
            $like = '%' . $search . '%';
            $query->where(function ($inner) use ($like) {
                $inner->where('liquidity_orders.order_id', 'like', $like)
                    ->orWhere('liquidity_orders.client_order_id', 'like', $like)
                    ->orWhere('liquidity_orders.instrument', 'like', $like)
                    ->orWhere('liquidity_orders.status', 'like', $like);
            });
        }

        return $query;
    }

    protected function combinedMonitoringData(Request $request, string $provider)
    {
        $aq = $this->aquanowGridQuery($request);

        if ($provider === 'aquanow') {
            $query = $aq;
        } else {
            $fbFiltered = $this->baseGridQuery();
            $this->applyDashboardFilters($fbFiltered, $request);
            // Reuse ledger-resolved tr_request_id from baseGridQuery (not NULL).
            $fb = DB::query()
                ->fromSub($fbFiltered->toBase(), 'ft')
                ->selectRaw("
                    ft.tr_request_id as id,
                    ft.id as source_id,
                    'fireblocks' as grid_source,
                    ft.fireblocks_tx_id as fireblocks_tx_id,
                    ft.direction as direction,
                    ft.status as status,
                    ft.created_at as created_at,
                    ft.updated_at as updated_at,
                    NULL as cr_tr_id,
                    ft.tr_request_id as tr_request_id,
                    NULL as instrument,
                    NULL as quantity,
                    NULL as price,
                    NULL as grand_total,
                    NULL as side,
                    ft.internal_status as internal_status,
                    ft.monitoring_state as monitoring_state,
                    ft.sub_status as sub_status
                ");
            $query = DB::query()->fromSub($fb->unionAll($aq), 'mg');
        }

        $dataTable = DataTables::of($query)
            ->addColumn('provider_badge', function ($row) {
                $source = $this->isAquanowGridRow($row) ? 'Aquanow' : 'Fireblocks';
                $class = $this->isAquanowGridRow($row) ? 'badge-info' : 'badge-secondary';

                return '<span class="badge ' . $class . '">' . e($source) . '</span>';
            })
            ->addColumn('fb_status_badge', function ($row) {
                if ($this->isAquanowGridRow($row)) {
                    $status = ucfirst(strtolower(trim((string) ($row->status ?? ''))));
                    $badge = FireblocksStatusHelper::internalStatusBadge($status !== '' ? $status : '—');

                    return '<span class="' . e($badge['class']) . '">' . e($badge['label']) . '</span>';
                }
                $record = $this->resolveCombinedFireblocksRecord($row);
                if (!$record) {
                    return e((string) ($row->status ?? '—'));
                }
                $statusCode = FireblocksStatusHelper::resolveStoredPrimaryStatus($record);
                if ($statusCode === '') {
                    $statusCode = strtoupper(trim((string) ($record->status ?? '')));
                }
                $badge = FireblocksStatusHelper::primaryStatusBadge($statusCode);

                return '<span class="badge ' . e($badge['class']) . '" title="' . e($statusCode) . '">'
                    . e($badge['label']) . '</span>';
            })
            ->addColumn('internal_status_badge', function ($row) {
                if ($this->isAquanowGridRow($row)) {
                    $status = ucfirst(strtolower(trim((string) ($row->status ?? ''))));
                    $badge = FireblocksStatusHelper::internalStatusBadge($status !== '' ? $status : '—');

                    return '<span class="' . e($badge['class']) . '">' . e($badge['label']) . '</span>';
                }
                $record = $this->resolveCombinedFireblocksRecord($row);
                if (!$record) {
                    return '—';
                }
                $resolved = FireblocksStatusHelper::resolveForMonitoring($record);
                $badge = FireblocksStatusHelper::internalStatusBadge($resolved['internal_status']);

                return '<span class="badge ' . $badge['class'] . '" title="' . e($resolved['message'] ?? '') . '">' . e($badge['label']) . '</span>';
            })
            ->addColumn('monitoring_badge', fn () => '—')
            ->addColumn('chainalysis_badge', function ($row) {
                if ($this->isAquanowGridRow($row)) {
                    return '<span class="text-muted">—</span>';
                }
                $record = $this->resolveCombinedFireblocksRecord($row);

                return $record
                    ? app(ChainalysisComplianceSyncService::class)->gridBadgeHtml($record)
                    : '<span class="text-muted">—</span>';
            })
            ->addColumn('queue_status_badge', fn () => '<span class="text-muted">—</span>')
            ->addColumn('retry_chain_badge', function ($row) {
                if ($this->isAquanowGridRow($row)) {
                    return '<span class="text-muted">—</span>';
                }
                $record = $this->resolveCombinedFireblocksRecord($row);

                return $record ? $this->retryChainService->gridBadgeHtml($record) : '<span class="text-muted">—</span>';
            })
            ->addColumn('reattempt_count', function ($row) {
                if ($this->isAquanowGridRow($row)) {
                    return 0;
                }

                return (int) ($this->resolveCombinedFireblocksRecord($row)?->auto_action_count ?? 0);
            })
            ->addColumn('last_reattempt_fmt', fn () => '<span class="text-muted">—</span>')
            ->addColumn('stuck_for', fn ($row) => '—')
            ->addColumn('account_label', function ($row) {
                if ($this->isAquanowGridRow($row)) {
                    $accountId = null;
                    if (!empty($row->cr_tr_id)) {
                        $accountId = DB::table('account_transactions')->where('id', $row->cr_tr_id)->value('account_id');
                    } elseif (!empty($row->tr_request_id)) {
                        $accountId = DB::table('account_transaction_requests')->where('id', $row->tr_request_id)->value('account_id');
                    }
                    if (!$accountId) {
                        return '<span class="text-muted">—</span>';
                    }
                    $account = DB::table('accounts')->where('id', $accountId)->first();

                    return $account
                        ? e($account->title) . '<br><small class="text-muted">' . e($account->account_number) . '</small>'
                        : '<span class="text-muted">—</span>';
                }
                $record = $this->resolveCombinedFireblocksRecord($row);

                return $this->ledgerAccountResolver->formatLabelHtml(
                    $record ? $this->ledgerAccountResolver->resolve($record) : null
                );
            })
            ->addColumn('amount_fmt', function ($row) {
                if ($this->isAquanowGridRow($row)) {
                    $qty = number_format((float) ($row->quantity ?? 0), 8);
                    $fiat = number_format((float) ($row->grand_total ?? 0), 2);

                    return e($qty . ' ' . ($row->instrument ?? '') . ' / ' . $fiat);
                }
                $record = $this->resolveCombinedFireblocksRecord($row);

                return $record ? FireblocksAmount::formatHtml($record) : '—';
            })
            ->addColumn('ledger_status_badge', function ($row) {
                if ($this->isAquanowGridRow($row)) {
                    return '<span class="badge badge-secondary">' . e((string) ($row->status ?? '—')) . '</span>';
                }
                $record = $this->resolveCombinedFireblocksRecord($row);
                if (!$record) {
                    return '<span class="text-muted">—</span>';
                }
                $info = $this->ledgerAccountResolver->resolve($record);
                $status = $info['ledger_status'] ?? null;
                if (empty($status)) {
                    return '<span class="text-muted">—</span>';
                }

                return '<span class="badge badge-secondary">' . e($status) . '</span>';
            })
            ->addColumn('actions', function ($row) {
                $html = '<div class="btn-group btn-group-sm" role="group">';
                if ($this->isAquanowGridRow($row)) {
                    if (!empty($row->cr_tr_id)) {
                        $html .= '<a href="' . e(url('/crypto-management/crypto-transactions/edit/crypto-transaction/' . $row->cr_tr_id)) . '" target="_blank" class="btn btn-outline-secondary" title="Open ledger"><i class="fal fa-external-link"></i></a>';
                    } elseif (!empty($row->tr_request_id)) {
                        $html .= '<a href="' . e(url('/crypto-management/crypto-pending-transactions/view/crypto-transaction/' . $row->tr_request_id)) . '" target="_blank" class="btn btn-outline-secondary" title="Open request"><i class="fal fa-external-link"></i></a>';
                    }
                } else {
                    $txId = e((string) $row->fireblocks_tx_id);
                    $html .= '<button type="button" class="btn btn-outline-primary btn-view-tx" data-tx-id="' . $txId . '" title="Quick view"><i class="fal fa-eye"></i></button>';
                    $record = $this->resolveCombinedFireblocksRecord($row);
                    $ledgerInfo = $record ? $this->ledgerAccountResolver->resolve($record) : null;
                    $ledgerUrl = '';
                    if (!empty($ledgerInfo['account_transaction_id'])) {
                        $ledgerUrl = url('/crypto-management/crypto-transactions/edit/crypto-transaction/' . $ledgerInfo['account_transaction_id']);
                    } elseif (!empty($ledgerInfo['tr_request_id'])) {
                        $ledgerUrl = url('/crypto-management/crypto-pending-transactions/view/crypto-transaction/' . $ledgerInfo['tr_request_id']);
                    }
                    if ($ledgerUrl) {
                        $html .= '<a href="' . e($ledgerUrl) . '" target="_blank" class="btn btn-outline-secondary" title="Open ledger record"><i class="fal fa-external-link"></i></a>';
                    }
                }
                $html .= '</div>';

                return $html;
            })
            ->editColumn('id', function ($row) {
                return $this->formatTrRequestIdCell($row->tr_request_id ?? $row->id ?? null);
            })
            ->editColumn('direction', function ($row) {
                if ($this->isAquanowGridRow($row)) {
                    $side = strtolower((string) ($row->side ?? ''));
                    if ($side === 'buy' || $side === 'sell') {
                        return e(ucfirst($side));
                    }
                }
                $direction = trim((string) ($row->direction ?? ''));

                return $direction !== '' ? e(ucfirst($direction)) : '<span class="text-muted">—</span>';
            })
            ->editColumn('sub_status', function ($row) {
                if ($this->isAquanowGridRow($row)) {
                    return '—';
                }
                $record = FireblocksTransaction::query()->find($row->id);
                if (!$record) {
                    return '—';
                }
                $subStatus = FireblocksStatusHelper::resolveStoredSubStatus($record);
                if ($subStatus === null || $subStatus === '') {
                    return '—';
                }
                $msg = FireblocksStatusHelper::getSubstatusMessage($subStatus) ?: $subStatus;
                $subBadge = FireblocksStatusHelper::substatusBadge($subStatus);

                return '<span class="' . e($subBadge['class']) . ' mr-1">' . e($subBadge['label']) . '</span>' . e($msg);
            })
            ->editColumn('created_at', function ($row) {
                $value = $row->created_at ?? null;
                if (!$value) {
                    return '—';
                }

                return Carbon::parse($value)->format('Y-m-d H:i:s');
            })
            ->editColumn('updated_at', function ($row) {
                $value = $row->updated_at ?? null;
                if (!$value) {
                    return '—';
                }

                return Carbon::parse($value)->format('Y-m-d H:i:s');
            })
            ->rawColumns([
                'id',
                'direction',
                'provider_badge',
                'fb_status_badge',
                'internal_status_badge',
                'monitoring_badge',
                'chainalysis_badge',
                'queue_status_badge',
                'retry_chain_badge',
                'last_reattempt_fmt',
                'account_label',
                'amount_fmt',
                'ledger_status_badge',
                'actions',
                'sub_status',
            ]);

        $dataTable->filter(function () {
            // Filters and search are applied on the Fireblocks / Aquanow source queries.
        }, true);

        // Sort by shared account_transaction_requests.id (tr_request_id), nulls last.
        $orderMap = [
            'fireblocks_transactions.id' => 'tr_request_id',
            'id' => 'tr_request_id',
            'fireblocks_transactions.fireblocks_tx_id' => 'fireblocks_tx_id',
            'fireblocks_tx_id' => 'fireblocks_tx_id',
            'fireblocks_transactions.direction' => 'direction',
            'direction' => 'direction',
            'fireblocks_transactions.created_at' => 'created_at',
            'created_at' => 'created_at',
            'fireblocks_transactions.updated_at' => 'updated_at',
            'updated_at' => 'updated_at',
            'fireblocks_transactions.auto_action_count' => 'tr_request_id',
            'fireblocks_transactions.last_reattempt_at' => 'updated_at',
            'fireblocks_transactions.stuck_detected_at' => 'updated_at',
            'fireblocks_transactions.chainalysis_status' => 'updated_at',
        ];
        foreach ($orderMap as $requestName => $column) {
            $dataTable->orderColumn($requestName, function ($query, $order) use ($column) {
                $dir = strtolower($order) === 'asc' ? 'asc' : 'desc';
                if ($column === 'tr_request_id') {
                    $query->reorder()
                        ->orderByRaw('tr_request_id IS NULL ASC')
                        ->orderBy('tr_request_id', $dir)
                        ->orderBy('source_id', $dir);

                    return;
                }
                $query->reorder()->orderBy($column, $dir);
            });
        }

        return $dataTable->make(true);
    }

    public function statusReference()
    {
        $config = MonitoringConfigService::tryGet();

        return response()->json([
            'success' => true,
            'catalog' => FireblocksStatusHelper::statusReferenceCatalog($config),
        ]);
    }

    public function show(string $txId)
    {
        $record = FireblocksTransaction::where('fireblocks_tx_id', $txId)->firstOrFail();
        $detail = $this->detailService->build($record);

        $detailHtml = view('fireblocks-monitoring::partials.transaction-detail-content', [
            'detail' => $detail,
            'layout' => 'modal',
            'showActions' => false,
            'showDashboardLink' => false,
            'showLedgerLink' => !empty($detail['ledger_url']),
        ])->render();

        return response()->json(array_merge([
            'success' => true,
            'detail' => $detail,
            'detail_html' => $detailHtml,
        ], $this->detailApiPayload($detail)));
    }

    /**
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    protected function detailApiPayload(array $detail): array
    {
        $record = $detail['transaction'];

        return [
            'transaction' => $record,
            'resolution' => $detail['resolution'],
            'account_transaction' => $detail['account_transaction'],
            'account' => $detail['account'],
            'logs' => $detail['logs'],
            'alerts' => $detail['alerts'],
            'ledger_url' => $detail['ledger_url'],
            'primary_status_label' => $detail['primary_status_label'],
            'substatus_message' => $detail['substatus_message'],
            'substatus_category' => $detail['substatus_category'],
            'internal_status_badge' => $detail['internal_status_badge'],
            'monitoring_badge' => $detail['monitoring_badge'],
            'queue_status' => $detail['queue_status'],
            'available_actions' => $detail['available_actions'],
            'retry_chain' => $detail['retry_chain'],
            'retry_chain_tree' => $detail['retry_chain_tree'],
            'amount_fmt' => $detail['amount_fmt'],
            'account_label' => $detail['account_label'],
            'stuck_for' => $detail['stuck_for'],
            'reattempt_count' => $detail['reattempt_count'],
            'last_reattempt_at' => $detail['last_reattempt_at'],
            'updated_at_fmt' => $detail['updated_at_fmt'],
            'default_fee_level' => $detail['default_fee_level'],
            'default_gas_price' => $detail['default_gas_price'],
            'chainalysis' => $detail['chainalysis'] ?? null,
        ];
    }

    protected function baseGridQuery(): Builder
    {
        return FireblocksTransaction::query()
            ->select('fireblocks_transactions.*')
            ->tap(fn (Builder $q) => MonitoringTransactionScope::applyLedgerAccountJoins($q))
            ->groupBy('fireblocks_transactions.id')
            ->addSelect([
                \DB::raw('MAX(at.id) as account_transaction_id'),
                \DB::raw('MAX(at.status) as ledger_status'),
                \DB::raw('MAX(COALESCE(acc_tr_ext.id, acc_tr_crypto.id, acc_tr_at.id, acc_at.id)) as grid_account_id'),
                \DB::raw('MAX(COALESCE(acc_tr_ext.title, acc_tr_crypto.title, acc_tr_at.title, acc_at.title)) as account_title'),
                \DB::raw('MAX(COALESCE(acc_tr_ext.account_number, acc_tr_crypto.account_number, acc_tr_at.account_number, acc_at.account_number)) as account_number'),
                \DB::raw('MAX(COALESCE(tr_ext.id, tr_crypto.id, tr_at.id)) as tr_request_id'),
            ]);
    }

    protected function applyDashboardFilters(Builder $query, Request $request): void
    {
        if ($request->filled('monitoring_state') && $request->monitoring_state !== 'all') {
            $query->where('fireblocks_transactions.monitoring_state', $request->monitoring_state);
        }

        if ($request->filled('fb_status') && $request->fb_status !== 'all') {
            $query->where('fireblocks_transactions.status', strtoupper($request->fb_status));
        }

        if ($request->filled('fb_sub_status') && $request->fb_sub_status !== 'all') {
            $matchCodes = FireblocksStatusHelper::substatusFilterMatchCodes($request->fb_sub_status);
            $query->whereIn('fireblocks_transactions.sub_status', $matchCodes);
        }

        if ($request->filled('internal_status') && $request->internal_status !== 'all') {
            FireblocksStatusHelper::applyInternalStatusFilter($query, (string) $request->internal_status);
        }

        if ($request->filled('direction') && $request->direction !== 'all') {
            $direction = strtolower((string) $request->direction);
            if (in_array($direction, ['buy', 'sell'], true)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('fireblocks_transactions.direction', $request->direction);
            }
        }

        if ($request->boolean('stuck_only')) {
            $query->where('fireblocks_transactions.monitoring_state', 'stuck');
        }

        $this->applyLedgerScopeFilter($query, $request);
    }

    protected function applyLedgerScopeFilter(Builder $query, Request $request): void
    {
        $scope = MonitoringTransactionScope::resolveLedgerScopeFromRequest($request);
        MonitoringTransactionScope::applyTransactionScope(
            $query,
            $scope,
            MonitoringConfigService::tryGet(),
            'fireblocks_transactions',
        );

        if (MonitoringTransactionScope::scopeRequiresLedgerAccount($scope)) {
            MonitoringTransactionScope::applyLedgerAccountPresent($query);
        }
    }

    /**
     * Replace the query ORDER BY so DataTables column clicks are not stacked
     * under the default created_at sort (GROUP BY + leftover order ignored sorts).
     */
    protected function applyGridOrder($query, string $column, string $order): void
    {
        $dir = strtolower($order) === 'asc' ? 'asc' : 'desc';
        $query->reorder()
            ->orderBy($column, $dir)
            ->orderByDesc('fireblocks_transactions.id');
    }

    /**
     * Sort Fireblocks-only grid by resolved TR request id (account_transaction_requests.id).
     */
    protected function applyTrRequestIdGridOrder($query, string $order): void
    {
        $dir = strtolower($order) === 'asc' ? 'asc' : 'desc';
        $query->reorder()
            ->orderByRaw('MAX(COALESCE(tr_ext.id, tr_crypto.id, tr_at.id)) IS NULL ASC')
            ->orderByRaw('MAX(COALESCE(tr_ext.id, tr_crypto.id, tr_at.id)) ' . $dir)
            ->orderByDesc('fireblocks_transactions.id');
    }

    protected function formatTrRequestIdCell(mixed $trRequestId): string
    {
        if ($trRequestId === null || $trRequestId === '') {
            return '<span class="text-muted" title="No linked TR request">—</span>';
        }

        return e((string) $trRequestId);
    }

    protected function resolveCombinedFireblocksRecord(object $row): ?FireblocksTransaction
    {
        if ($this->isAquanowGridRow($row)) {
            return null;
        }

        if (!empty($row->source_id)) {
            return FireblocksTransaction::query()->find($row->source_id);
        }

        if (!empty($row->fireblocks_tx_id)) {
            return FireblocksTransaction::query()
                ->where('fireblocks_tx_id', $row->fireblocks_tx_id)
                ->first();
        }

        return null;
    }

    protected function mergeActionOverrides(Request $request, array $config): array
    {
        if ($request->filled('fee_level') && in_array(strtoupper($request->fee_level), ['LOW', 'MEDIUM', 'HIGH'], true)) {
            $config['boost_fee_level'] = strtoupper($request->fee_level);
        }

        if ($request->has('gas_price')) {
            $config['boost_gas_price'] = trim((string) $request->gas_price);
        }

        return $config;
    }

    public function sync(Request $request, string $txId)
    {
        $record = FireblocksTransaction::where('fireblocks_tx_id', $txId)->firstOrFail();
        $result = $this->actionService->syncStatus($record, 'manual');

        return response()->json($result);
    }

    public function boost(Request $request, string $txId)
    {
        $record = FireblocksTransaction::where('fireblocks_tx_id', $txId)->firstOrFail();
        $config = MonitoringConfigService::tryGet() ?: [];
        $result = $this->actionService->queueOrExecute('boost', $record, 'manual', $this->mergeActionOverrides($request, $config));

        return response()->json($result);
    }

    public function drop(Request $request, string $txId)
    {
        $record = FireblocksTransaction::where('fireblocks_tx_id', $txId)->firstOrFail();
        $config = MonitoringConfigService::tryGet() ?: [];
        $result = $this->actionService->queueOrExecute('drop', $record, 'manual', $this->mergeActionOverrides($request, $config));

        return response()->json($result);
    }

    public function resubmit(Request $request, string $txId)
    {
        $record = FireblocksTransaction::where('fireblocks_tx_id', $txId)->firstOrFail();
        $result = $this->actionService->queueOrExecute('resubmit', $record, 'manual');

        return response()->json($result);
    }

    public function approveAlert(Request $request, int $id)
    {
        $alert = FireblocksTransactionAlert::findOrFail($id);
        $record = FireblocksTransaction::where('fireblocks_tx_id', $alert->fireblocks_tx_id)->firstOrFail();
        $action = $request->input('action', 'boost');

        $result = $this->actionService->queueOrExecute($action, $record, 'manual');
        $alert->status = 'actioned';
        $alert->action_taken = $action;
        $alert->action_taken_at = now();
        $alert->save();

        return response()->json($result);
    }

    public function dismissAlert(Request $request, int $id)
    {
        $alert = FireblocksTransactionAlert::findOrFail($id);
        $alert->status = 'dismissed';
        $alert->dismissed_by = auth()->id();
        $alert->dismissed_at = now();
        $alert->save();

        return response()->json(['success' => true, 'message' => 'Alert dismissed']);
    }
}
