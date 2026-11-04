<?php

namespace App\Modules\FireblocksVaultSweeping\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepLog;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Services\SweepConfigService;
use App\Modules\FireblocksVaultSweeping\Services\SweepJobRecoveryService;
use App\Modules\FireblocksVaultSweeping\Services\SweepRuleRunHistoryService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SweepDashboardController extends Controller
{
    public function __construct(
        protected SweepRuleRunHistoryService $runHistory,
        protected SweepJobRecoveryService $recovery,
    ) {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:view fireblocks vault sweeping');
        $this->middleware('permission:manage fireblocks vault sweeping', [
            'only' => ['recoverReconcile', 'recoverRequeue', 'recoverExecute'],
        ]);
    }

    public function index()
    {
        $config = SweepConfigService::tryGet();
        $summary = [
            'pending' => FireblocksSweepJob::whereIn('status', [
                FireblocksSweepJob::STATUS_PENDING,
                FireblocksSweepJob::STATUS_QUEUED,
                FireblocksSweepJob::STATUS_GAS_FUNDED,
            ])->count(),
            'waiting_for_gas' => FireblocksSweepJob::where('status', FireblocksSweepJob::STATUS_WAITING_FOR_GAS)->count(),
            'submitted' => FireblocksSweepJob::where('status', FireblocksSweepJob::STATUS_SUBMITTED)->count(),
            'completed' => FireblocksSweepJob::where('status', FireblocksSweepJob::STATUS_COMPLETED)->count(),
            'skipped' => FireblocksSweepJob::where('status', FireblocksSweepJob::STATUS_SKIPPED)->count(),
            'failed' => FireblocksSweepJob::where('status', FireblocksSweepJob::STATUS_FAILED)->count(),
            'rules' => FireblocksSweepRule::where('enabled', true)->count(),
        ];

        return view('fireblocks-sweeping::index', compact('config', 'summary'));
    }

    public function data(Request $request)
    {
        $query = FireblocksSweepJob::query()
            ->with('rule')
            ->orderByDesc('id');

        if ($status = $request->input('status')) {
            if ($status === 'pending') {
                $query->whereIn('status', [
                    FireblocksSweepJob::STATUS_PENDING,
                    FireblocksSweepJob::STATUS_QUEUED,
                    FireblocksSweepJob::STATUS_GAS_FUNDED,
                ]);
            } else {
                $query->where('status', $status);
            }
        }

        return DataTables::of($query)
            ->addColumn('rule_name', fn (FireblocksSweepJob $job) => $job->rule?->name ?? '—')
            ->editColumn('rule_id', function (FireblocksSweepJob $job) {
                if (!$job->rule_id) {
                    return '—';
                }

                $url = route('fireblocks.vault-sweeping.rules.show', $job->rule_id);

                return '<a href="' . e($url) . '">' . e((string) $job->rule_id) . '</a>';
            })
            ->editColumn('source_vault_id', fn (FireblocksSweepJob $job) => '<small>' . e($this->runHistory->vaultLabel((string) $job->source_vault_id)) . '</small>')
            ->editColumn('destination_vault_id', fn (FireblocksSweepJob $job) => '<small>' . e($this->runHistory->vaultLabel((string) $job->destination_vault_id)) . '</small>')
            ->addColumn('actions', function (FireblocksSweepJob $job) {
                $url = route('fireblocks.vault-sweeping.jobs.show', $job);
                $html = '<a href="' . e($url) . '" class="btn btn-xs btn-outline-primary mb-1" title="View job details">'
                    . '<i class="fal fa-eye"></i> Detail</a>';
                $recovery = $this->recovery->renderActionButtons($job, 'btn-xs');
                if ($recovery !== '') {
                    $html .= '<div class="mt-1">' . $recovery . '</div>';
                }

                return $html;
            })
            ->editColumn('amount', fn (FireblocksSweepJob $job) => $job->amount ?? '—')
            ->editColumn('status', function (FireblocksSweepJob $job) {
                $class = $this->runHistory->statusBadgeClass($job->status);

                return '<span class="badge ' . $class . '">' . e($this->runHistory->statusLabel($job->status)) . '</span>';
            })
            ->addColumn('fireblocks_status_display', function (FireblocksSweepJob $job) {
                $label = $this->runHistory->formatFireblocksStatus($job);
                if ($label === '—') {
                    return '—';
                }

                return '<small>' . e($label) . '</small>';
            })
            ->editColumn('fireblocks_status_updated_at', fn (FireblocksSweepJob $job) => $this->runHistory->formatStatusUpdatedAt($job) ?? '—')
            ->editColumn('fireblocks_tx_id', fn (FireblocksSweepJob $job) => $job->fireblocks_tx_id
                ? '<code class="small">' . e($job->fireblocks_tx_id) . '</code>'
                : '—')
            ->editColumn('created_at', fn (FireblocksSweepJob $job) => optional($job->created_at)->format('Y-m-d H:i:s'))
            ->rawColumns(['actions', 'status', 'fireblocks_status_display', 'fireblocks_tx_id', 'rule_id', 'source_vault_id', 'destination_vault_id'])
            ->make(true);
    }

    public function show(FireblocksSweepJob $job)
    {
        $job->load(['rule']);
        $recoveryActions = $this->recovery->availableActions($job);

        return view('fireblocks-sweeping::show', [
            'job' => $job,
            'recoveryActions' => $recoveryActions,
            'runHistory' => $this->runHistory,
        ]);
    }

    public function recoverReconcile(FireblocksSweepJob $job)
    {
        $result = $this->recovery->reconcile($job);

        return redirect()
            ->route('fireblocks.vault-sweeping.jobs.show', $job)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function recoverRequeue(FireblocksSweepJob $job)
    {
        $result = $this->recovery->requeue($job);

        return redirect()
            ->route('fireblocks.vault-sweeping.jobs.show', $job)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function recoverExecute(FireblocksSweepJob $job)
    {
        $result = $this->recovery->execute($job);

        return redirect()
            ->route('fireblocks.vault-sweeping.jobs.show', $job)
            ->with($result['success'] ? 'success' : 'warning', $result['message']);
    }

    public function logsData(FireblocksSweepJob $job, Request $request)
    {
        $query = FireblocksSweepLog::query()
            ->where('sweep_job_id', $job->id)
            ->orderByDesc('id');

        if ($scope = $request->input('scope')) {
            if ($scope === 'timeline') {
                $query->where(function ($q) {
                    $q->where('event', 'webhook_status_update')
                        ->orWhereIn('event', [
                            'sweep_submitted',
                            'sweep_completed',
                            'sweep_failed',
                            'gas_feed_confirmed',
                            'gas_feed_failed',
                            'max_spendable_fallback',
                            'sweep_max_spendable_failed',
                            'sweep_local_record_failed',
                            'gas_estimate_failed',
                            'gas_fee_estimated',
                            'gas_relay_ready',
                            'gas_feed_submit_failed',
                            'fireblocks_api_request',
                            'gas_local_record_failed',
                        ]);
                });
            }
        }

        return DataTables::of($query)
            ->addColumn('created_at_fmt', fn (FireblocksSweepLog $log) => $log->created_at?->format('Y-m-d H:i:s') ?? '—')
            ->addColumn('event_code', fn (FireblocksSweepLog $log) => '<code class="small">' . e($log->event) . '</code>')
            ->addColumn('level_label', fn (FireblocksSweepLog $log) => e($log->level ?? '—'))
            ->addColumn('message_fmt', function (FireblocksSweepLog $log) {
                $html = e($log->message ?? '');
                if (!empty($log->context)) {
                    $json = json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    $html .= '<details class="mt-1"><summary class="small text-primary" style="cursor:pointer">Context</summary>'
                        . '<pre class="small mb-0 mt-1 p-2 bg-light border rounded">' . e($json) . '</pre></details>';
                }

                return $html;
            })
            ->rawColumns(['event_code', 'message_fmt'])
            ->make(true);
    }
}
