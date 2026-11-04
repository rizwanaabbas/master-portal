<?php

namespace App\Modules\FireblocksVaultSweeping\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fireblocks\Models\Asset;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Services\SweepConfigService;
use App\Modules\FireblocksVaultSweeping\Services\SweepDryRunService;
use App\Modules\FireblocksVaultSweeping\Services\SweepEvaluationService;
use App\Modules\FireblocksVaultSweeping\Services\SweepGasRelayStatusService;
use App\Modules\FireblocksVaultSweeping\Services\SweepRuleFlowDiagramService;
use App\Modules\FireblocksVaultSweeping\Services\SweepScheduleService;
use App\Modules\FireblocksVaultSweeping\Support\SweepAssetMetadata;
use App\Modules\FireblocksVaultSweeping\Support\SweepRuleExport;
use App\Modules\FireblocksVaultSweeping\Services\SweepJobRecoveryService;
use App\Modules\FireblocksVaultSweeping\Services\SweepRuleRunHistoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class SweepRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:view fireblocks vault sweeping', [
            'only' => [
                'index', 'data', 'show', 'create', 'edit', 'jobsData', 'gasRelayStatus', 'dryRun', 'export',
            ],
        ]);
        $this->middleware('permission:manage fireblocks vault sweeping', [
            'only' => ['store', 'update', 'destroy', 'run', 'reorderPriorities', 'import'],
        ]);
    }

    public function index()
    {
        $moduleConfig = SweepConfigService::tryGet() ?? [];
        $sweepingActive = SweepConfigService::isActive();

        $sourceAssets = FireblocksSweepRule::query()
            ->select('asset_ids')
            ->whereNotNull('asset_ids')
            ->where('asset_ids', '!=', '')
            ->distinct()
            ->orderBy('asset_ids')
            ->pluck('asset_ids');

        return view('fireblocks-sweeping::rules.index', compact(
            'moduleConfig',
            'sweepingActive',
            'sourceAssets',
        ));
    }

    public function data(Request $request, SweepScheduleService $schedule)
    {
        $moduleConfig = SweepConfigService::tryGet() ?? [];

        $query = FireblocksSweepRule::query()
            ->select('fireblocks_sweep_rules.*');

        $this->applyRulesGridFilters($query, $request);

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $search = trim((string) $request->input('search.value', ''));
                if ($search === '') {
                    return;
                }

                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
                $query->where(function ($q) use ($like, $search) {
                    $q->where('name', 'like', $like)
                        ->orWhere('asset_ids', 'like', $like)
                        ->orWhere('target_asset_id', 'like', $like)
                        ->orWhere('trigger_modes', 'like', $like)
                        ->orWhere('destination_vault_id', 'like', $like);

                    if (is_numeric($search)) {
                        $q->orWhere('id', (int) $search)
                            ->orWhere('priority', (int) $search);
                    }
                });
            })
            ->orderColumn('priority', 'fireblocks_sweep_rules.priority $1, fireblocks_sweep_rules.id $1')
            ->addColumn('drag_handle', fn () => '<span class="sweep-rule-drag-handle" title="Drag to reorder"><i class="fal fa-grip-vertical"></i></span>')
            ->editColumn('priority', fn (FireblocksSweepRule $rule) => '<code class="rule-priority">' . e((string) $rule->priority) . '</code>')
            ->addColumn('rule_link', function (FireblocksSweepRule $rule) {
                $url = route('fireblocks.vault-sweeping.rules.show', $rule);

                return '<a href="' . e($url) . '"><code>#' . e((string) $rule->id) . '</code></a>';
            })
            ->addColumn('schedule_label', function (FireblocksSweepRule $rule) use ($schedule, $moduleConfig) {
                if (!$rule->supportsTrigger('scheduled')) {
                    return '<span class="text-muted">—</span>';
                }

                return '<small>' . e($schedule->describeSchedule($rule, $moduleConfig)) . '</small>';
            })
            ->addColumn('source_asset', fn (FireblocksSweepRule $rule) => '<code>' . e($rule->asset_ids ?: '—') . '</code>')
            ->addColumn('target_asset', fn (FireblocksSweepRule $rule) => '<code>' . e($rule->targetAssetId() ?: '—') . '</code>')
            ->addColumn('triggers_label', fn (FireblocksSweepRule $rule) => e($this->formatTriggerModes($rule->trigger_modes)))
            ->addColumn('enabled_badge', fn (FireblocksSweepRule $rule) => $this->formatEnabledBadge($rule))
            ->addColumn('actions', function (FireblocksSweepRule $rule) {
                $url = route('fireblocks.vault-sweeping.rules.show', $rule);

                return '<a href="' . e($url) . '" class="btn btn-primary btn-sm sweep-rule-detail-btn">'
                    . '<i class="fal fa-eye mr-1"></i> Detail</a>';
            })
            ->rawColumns([
                'drag_handle',
                'priority',
                'rule_link',
                'schedule_label',
                'source_asset',
                'target_asset',
                'enabled_badge',
                'actions',
            ])
            ->make(true);
    }

    protected function applyRulesGridFilters($query, Request $request): void
    {
        if ($request->boolean('enabled_only')) {
            $query->where('enabled', true);
        }

        $enabled = $request->input('enabled');
        if ($enabled === '1' || $enabled === 1 || $enabled === true || $enabled === 'true') {
            $query->where('enabled', true);
        } elseif ($enabled === '0' || $enabled === 0 || $enabled === false || $enabled === 'false') {
            $query->where('enabled', false);
        }

        $triggerMode = strtolower(trim((string) $request->input('trigger_mode', '')));
        if ($triggerMode === 'deposit') {
            $query->where('trigger_modes', 'like', '%deposit%');
        } elseif ($triggerMode === 'scheduled') {
            $query->where('trigger_modes', 'like', '%scheduled%');
        } elseif ($triggerMode === 'both') {
            $query->where('trigger_modes', 'like', '%deposit%')
                ->where('trigger_modes', 'like', '%scheduled%');
        }

        $sourceAsset = trim((string) $request->input('source_asset', ''));
        if ($sourceAsset !== '') {
            $query->where('asset_ids', $sourceAsset);
        }

        $ruleDryRun = $request->input('rule_dry_run');
        if ($ruleDryRun === '1' || $ruleDryRun === 1 || $ruleDryRun === 'true') {
            $query->where('dry_run', true);
        } elseif ($ruleDryRun === '0' || $ruleDryRun === 0 || $ruleDryRun === 'false') {
            $query->where('dry_run', false);
        }
    }

    protected function formatEnabledBadge(FireblocksSweepRule $rule): string
    {
        $html = $rule->enabled
            ? '<span class="badge badge-success">Active</span>'
            : '<span class="badge badge-secondary">Disabled</span>';

        if ($rule->dry_run) {
            $html .= ' <span class="badge badge-warning">Dry run</span>';
        }

        return $html;
    }

    public function jobsData(FireblocksSweepRule $rule, SweepRuleRunHistoryService $runHistory, SweepJobRecoveryService $recovery)
    {
        $query = FireblocksSweepJob::query()
            ->where('rule_id', $rule->id)
            ->orderByDesc('id');

        return DataTables::of($query)
            ->addColumn('created_at_fmt', fn (FireblocksSweepJob $job) => '<small>' . e($job->created_at?->format('Y-m-d H:i:s') ?? '—') . '</small>')
            ->addColumn('trigger_badge', function (FireblocksSweepJob $job) use ($runHistory) {
                $class = $runHistory->triggerBadgeClass($job->trigger_source);
                $label = $runHistory->triggerLabel($job->trigger_source);

                return '<span class="badge ' . e($class) . '">' . e($label) . '</span>';
            })
            ->addColumn('deposit_tx', function (FireblocksSweepJob $job) use ($runHistory) {
                $depositTx = $runHistory->formatDepositTxId($job);

                return $depositTx
                    ? '<small><code>' . e($depositTx) . '</code></small>'
                    : '<small class="text-muted">—</small>';
            })
            ->addColumn('source_vault', function (FireblocksSweepJob $job) use ($runHistory) {
                return '<small>' . e($runHistory->vaultLabel($job->source_vault_id)) . '</small>';
            })
            ->addColumn('asset_code', fn (FireblocksSweepJob $job) => '<code class="small">' . e($job->asset_id) . '</code>')
            ->addColumn('status_badge', function (FireblocksSweepJob $job) use ($runHistory) {
                $class = $runHistory->statusBadgeClass($job->status);
                $label = $runHistory->statusLabel($job->status);

                return '<span class="badge ' . e($class) . '">' . e($label) . '</span>';
            })
            ->addColumn('fireblocks_status_fmt', function (FireblocksSweepJob $job) use ($runHistory) {
                return '<small>' . e($runHistory->formatFireblocksStatus($job)) . '</small>';
            })
            ->addColumn('status_updated_fmt', function (FireblocksSweepJob $job) use ($runHistory) {
                return '<small>' . e($runHistory->formatStatusUpdatedAt($job) ?? '—') . '</small>';
            })
            ->addColumn('amount_fmt', fn (FireblocksSweepJob $job) => '<small>' . e($job->amount ?? '—') . '</small>')
            ->addColumn('details', function (FireblocksSweepJob $job) use ($runHistory) {
                if ($job->status === 'failed' || $job->status === 'skipped') {
                    return '<small class="text-muted">' . e($runHistory->humanizeSkipReason($job->skip_reason)) . '</small>';
                }

                if ($job->fireblocks_tx_id) {
                    return '<small>Sweep tx: <code>' . e($job->fireblocks_tx_id) . '</code></small>';
                }

                $depositTx = $runHistory->formatDepositTxId($job);
                if ($depositTx) {
                    return '<small>From deposit: <code>' . e($depositTx) . '</code></small>';
                }

                return '<small>—</small>';
            })
            ->addColumn('view_link', function (FireblocksSweepJob $job) use ($recovery) {
                $url = route('fireblocks.vault-sweeping.jobs.show', $job);
                $html = '<a href="' . e($url) . '" class="btn btn-xs btn-outline-secondary mb-1">View</a>';
                $buttons = $recovery->renderActionButtons($job, 'btn-xs');
                if ($buttons !== '') {
                    $html .= '<div class="mt-1">' . $buttons . '</div>';
                }

                return $html;
            })
            ->rawColumns([
                'created_at_fmt',
                'trigger_badge',
                'deposit_tx',
                'source_vault',
                'asset_code',
                'status_badge',
                'fireblocks_status_fmt',
                'status_updated_fmt',
                'amount_fmt',
                'details',
                'view_link',
            ])
            ->make(true);
    }

    public function create()
    {
        return view('fireblocks-sweeping::rules.form', [
            'rule' => $this->newRuleTemplate(),
            'moduleConfig' => SweepConfigService::tryGet() ?? [],
            'assetCatalogue' => $this->assetCatalogue(),
            'defaultTronStation' => trim((string) ((SweepConfigService::tryGet() ?? [])['default_tron_station_vault_id'] ?? '')),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        FireblocksSweepRule::create($data);

        return redirect()->route('fireblocks.vault-sweeping.rules.index')->with('success', 'Sweep rule created.');
    }

    public function export(FireblocksSweepRule $rule)
    {
        $filename = 'sweep-rule-' . $rule->id . '-' . preg_replace('/[^a-z0-9_-]+/i', '-', trim($rule->name)) . '.json';
        $filename = preg_replace('/-+/', '-', $filename) ?: 'sweep-rule-' . $rule->id . '.json';

        try {
            $json = json_encode(
                SweepRuleExport::document($rule),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            return redirect()
                ->back()
                ->with('error', 'Could not export rule as JSON: ' . $e->getMessage());
        }

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'nullable|file|max:512',
            'import_json' => 'nullable|string|max:65536',
        ]);

        $raw = trim((string) $request->input('import_json', ''));
        if ($request->hasFile('import_file')) {
            $contents = $request->file('import_file')->get();
            if ($contents !== false && trim($contents) !== '') {
                $raw = trim($contents);
            }
        }

        if ($raw === '') {
            throw ValidationException::withMessages([
                'import_file' => 'Choose a JSON file or paste export JSON.',
            ]);
        }

        try {
            $document = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                'import_file' => 'Invalid JSON.',
            ]);
        }

        if (!is_array($document)) {
            throw ValidationException::withMessages([
                'import_file' => 'Invalid export format.',
            ]);
        }

        $attributes = SweepRuleExport::attributesForCreate($document);
        $rule = FireblocksSweepRule::create($attributes);

        return redirect()
            ->route('fireblocks.vault-sweeping.rules.show', $rule)
            ->with('success', 'Sweep rule imported as #' . $rule->id . ' (“' . $rule->name . '”).');
    }

    public function show(
        FireblocksSweepRule $rule,
        SweepRuleRunHistoryService $runHistory,
        SweepScheduleService $schedule,
        SweepRuleFlowDiagramService $flowDiagram
    ) {
        $moduleConfig = SweepConfigService::tryGet() ?? [];
        $scheduleDescription = $schedule->describeSchedule($rule, $moduleConfig);

        $lastScheduledJob = FireblocksSweepJob::query()
            ->where('rule_id', $rule->id)
            ->where('trigger_source', 'scheduled')
            ->orderByDesc('id')
            ->first();

        return view('fireblocks-sweeping::rules.show', [
            'rule' => $rule,
            'moduleConfig' => $moduleConfig,
            'scheduleDescription' => $scheduleDescription,
            'flowDiagram' => $flowDiagram->build($rule, $moduleConfig, $scheduleDescription),
            'lastScheduledJob' => $lastScheduledJob,
            'runHistorySummary' => $runHistory->summaryForRule($rule),
            'runHistoryService' => $runHistory,
            'manualRunPreflight' => $runHistory->manualRunPreflight($rule),
            'sweepingActive' => SweepConfigService::isActive(),
        ]);
    }

    public function gasRelayStatus(Request $request, SweepGasRelayStatusService $gasRelay)
    {
        $assetId = trim((string) $request->query('asset_id', ''));
        if ($assetId === '') {
            return response()->json(['error' => 'asset_id is required'], 422);
        }

        $destinationVaultId = trim((string) $request->query('destination_vault_id', ''));
        $sourceVaultId = trim((string) $request->query('source_vault_id', ''));

        return response()->json(
            $gasRelay->probe(
                $assetId,
                $destinationVaultId !== '' ? $destinationVaultId : null,
                $sourceVaultId !== '' ? $sourceVaultId : null
            )
        );
    }

    public function edit(FireblocksSweepRule $rule, SweepRuleRunHistoryService $runHistory)
    {
        $moduleConfig = SweepConfigService::tryGet() ?? [];

        return view('fireblocks-sweeping::rules.form', [
            'rule' => $rule,
            'moduleConfig' => $moduleConfig,
            'assetCatalogue' => $this->assetCatalogue(),
            'defaultTronStation' => trim((string) ($moduleConfig['default_tron_station_vault_id'] ?? '')),
            'runHistorySummary' => $rule->exists ? $runHistory->summaryForRule($rule) : null,
            'runHistoryService' => $runHistory,
            'manualRunPreflight' => $rule->exists ? $runHistory->manualRunPreflight($rule) : null,
            'sweepingActive' => SweepConfigService::isActive(),
        ]);
    }

    public function update(Request $request, FireblocksSweepRule $rule)
    {
        $rule->update($this->validated($request));

        return redirect()->route('fireblocks.vault-sweeping.rules.index')->with('success', 'Sweep rule updated.');
    }

    public function destroy(FireblocksSweepRule $rule)
    {
        $rule->update(['enabled' => false]);
        $rule->delete();

        return redirect()
            ->route('fireblocks.vault-sweeping.rules.index')
            ->with('success', 'Sweep rule deleted (archived). It will no longer run; job history is preserved.');
    }

    public function reorderPriorities(Request $request)
    {
        $data = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|exists:fireblocks_sweep_rules,id',
        ]);

        foreach ($data['order'] as $index => $ruleId) {
            FireblocksSweepRule::query()
                ->where('id', $ruleId)
                ->update(['priority' => ($index + 1) * 10]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rule priorities updated.',
        ]);
    }

    public function run(FireblocksSweepRule $rule, Request $request, SweepEvaluationService $evaluation, SweepRuleRunHistoryService $runHistory)
    {
        if (!SweepConfigService::isActive()) {
            return redirect()
                ->to($this->runRedirectTarget($rule, $request))
                ->with('error', $this->sweepingInactiveMessage());
        }

        if (!$rule->isRunnable()) {
            return redirect()
                ->to($this->runRedirectTarget($rule, $request))
                ->with('error', 'This rule is disabled or archived and cannot be run.');
        }

        $preflight = $runHistory->manualRunPreflight($rule);
        $forceResubmit = $request->boolean('force_resubmit');

        if ($preflight['has_prior_run'] && !$forceResubmit) {
            return redirect()
                ->to($this->runRedirectTarget($rule, $request))
                ->with('error', 'This rule has already been run. Use Manual run and confirm "Skip idempotency and resubmit" to run again.');
        }

        $result = $evaluation->evaluateRule($rule, null, $forceResubmit);
        $message = $result['message'] ?? 'Rule execution finished.';

        if (($result['submitted'] ?? 0) === 0 && ($result['skipped'] ?? 0) > 0 && !$evaluation->isDryRunContext(null, $rule)) {
            $message .= ' See run history below for skip details.';
        }

        if ($evaluation->isDryRunContext(null, $rule)) {
            $message .= ' Dry run is enabled — turn off rule/global dry run to submit Fireblocks transactions.';
        }

        $flashType = ($result['failed'] ?? 0) > 0
            ? 'error'
            : (($result['submitted'] ?? 0) > 0 ? 'success' : 'info');

        return redirect()
            ->to($this->runRedirectTarget($rule, $request))
            ->with($result['success'] ?? false ? $flashType : 'error', $message);
    }

    protected function runRedirectTarget(FireblocksSweepRule $rule, Request $request): string
    {
        return match ($request->input('redirect')) {
            'edit' => route('fireblocks.vault-sweeping.rules.edit', $rule),
            'show', 'detail' => route('fireblocks.vault-sweeping.rules.show', $rule),
            default => route('fireblocks.vault-sweeping.rules.index'),
        };
    }

    public function dryRun(FireblocksSweepRule $rule, Request $request, SweepDryRunService $dryRun, SweepEvaluationService $evaluation)
    {
        @set_time_limit(120);

        if (!$rule->isRunnable() && $request->isMethod('post')) {
            return redirect()
                ->route('fireblocks.vault-sweeping.rules.dry-run', $rule)
                ->with('error', 'This rule is disabled or archived.');
        }

        if ($request->isMethod('post')) {
            return $this->dryRunResult($rule, $dryRun, $evaluation, $request->boolean('persist_jobs'));
        }

        $report = $dryRun->previewBefore($rule);

        return view('fireblocks-sweeping::rules.dry-run', [
            'rule' => $rule,
            'report' => $report,
            'executed' => false,
        ]);
    }

    protected function dryRunResult(
        FireblocksSweepRule $rule,
        SweepDryRunService $dryRun,
        SweepEvaluationService $evaluation,
        bool $persistJobs
    ) {
        @set_time_limit(120);

        $beforeReport = $dryRun->previewBefore($rule);

        if ($persistJobs) {
            $evaluation->evaluateRule($rule);
        }

        $afterReport = $dryRun->simulate($rule);

        return view('fireblocks-sweeping::rules.dry-run', [
            'rule' => $rule,
            'beforeReport' => $beforeReport,
            'afterReport' => $afterReport,
            'executed' => true,
            'persistJobs' => $persistJobs,
        ]);
    }

    protected function sweepingInactiveMessage(): string
    {
        return 'Sweeping is not active. Enable the FireblocksVaultSweeping module and turn on '
            . '"Sweeping Enabled" in Settings → Module Configurations → Fireblocks Vault Sweeping.';
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'enabled' => 'nullable|boolean',
            'asset_id' => 'required|string|max:128',
            'target_asset_id' => 'nullable|string|max:128',
            'gas_funding_vault_id' => 'nullable|string|max:64',
            'feed_trx_amount' => 'nullable|numeric|min:0',
            'destination_vault_id' => 'nullable|string|max:64',
            'min_balance' => 'nullable|numeric|min:0',
            'scheduled_min_balance' => 'nullable|numeric|min:0',
            'priority' => 'nullable|integer|min:1|max:99999',
            'schedule_interval_minutes' => 'nullable|integer|min:1|max:525600',
            'schedule_run_at' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'trigger_deposit' => 'nullable|boolean',
            'trigger_scheduled' => 'nullable|boolean',
            'deposit_delay_minutes' => 'nullable|integer|min:0|max:1440',
            'sweep_orphan_deposits' => 'nullable|boolean',
            'dry_run' => 'nullable|boolean',
            'use_gasless' => 'nullable|boolean',
            'treat_as_gross_amount' => 'nullable|boolean',
            'fee_level' => 'nullable|in:LOW,MEDIUM,HIGH',
            'sweep_amount_source' => 'nullable|in:max_spendable,available',
            'source_type' => 'nullable|in:client_deposit_vaults,vault_id_list,vault_name_prefix',
            'source_vault_ids' => 'nullable|string',
        ]);

        $triggerModes = [];
        if ($request->boolean('trigger_deposit')) {
            $triggerModes[] = 'deposit';
        }
        if ($request->boolean('trigger_scheduled')) {
            $triggerModes[] = 'scheduled';
        }

        $scheduleRunAt = trim((string) ($data['schedule_run_at'] ?? ''));
        if ($scheduleRunAt === '') {
            $scheduleRunAt = null;
        } elseif (!preg_match('/^(\d{2}):(\d{2})$/', $scheduleRunAt, $timeParts)
            || (int) $timeParts[1] > 23
            || (int) $timeParts[2] > 59) {
            throw ValidationException::withMessages([
                'schedule_run_at' => 'Run time must be a valid 24-hour time (HH:MM).',
            ]);
        }

        if ($triggerModes === []) {
            throw ValidationException::withMessages([
                'trigger_deposit' => 'Enable at least one trigger: deposit webhook and/or scheduled dust cleanup.',
            ]);
        }

        $assetId = trim((string) $data['asset_id']);
        if (str_contains($assetId, ',')) {
            throw ValidationException::withMessages([
                'asset_id' => 'One source asset per rule only. Create a separate rule for each asset.',
            ]);
        }

        $targetAssetId = trim((string) ($data['target_asset_id'] ?? ''));
        if ($targetAssetId === '') {
            $targetAssetId = $assetId;
        }

        $useGasless = $request->boolean('use_gasless');
        $assetRecord = Asset::query()->with('network')->where('asset_id', $assetId)->first();
        $assetMeta = SweepAssetMetadata::describe($assetRecord, $assetId);
        if ($useGasless && empty($assetMeta['uses_gas_relay'])) {
            throw ValidationException::withMessages([
                'use_gasless' => 'Gas relay (useGasless) applies to Ethereum ERC-20 USDC/USDT only.',
            ]);
        }

        $treatAsGross = $request->boolean('treat_as_gross_amount');
        if ($treatAsGross && !SweepAssetMetadata::isNativeAccountAsset($assetId)) {
            throw ValidationException::withMessages([
                'treat_as_gross_amount' => 'Treat as gross amount applies to native/base assets only (e.g. ETH_TEST5, TRX).',
            ]);
        }

        if ($treatAsGross && $useGasless) {
            throw ValidationException::withMessages([
                'treat_as_gross_amount' => 'Cannot combine treat as gross amount with gas relay (useGasless).',
            ]);
        }

        return [
            'name' => $data['name'],
            'priority' => (int) ($data['priority'] ?? 100),
            'enabled' => $request->boolean('enabled'),
            'source_type' => $data['source_type'] ?? 'client_deposit_vaults',
            'source_vault_ids' => trim((string) ($data['source_vault_ids'] ?? '')),
            'destination_vault_id' => trim((string) ($data['destination_vault_id'] ?? '')),
            'asset_ids' => $assetId,
            'target_asset_id' => $targetAssetId,
            'gas_funding_vault_id' => trim((string) ($data['gas_funding_vault_id'] ?? '')),
            'feed_trx_amount' => $data['feed_trx_amount'] ?? null,
            'use_gasless' => $useGasless,
            'treat_as_gross_amount' => $treatAsGross,
            'min_balance' => $data['min_balance'] ?? 0,
            'scheduled_min_balance' => $data['scheduled_min_balance'] ?? 0,
            'schedule_interval_minutes' => $scheduleRunAt !== null
                ? null
                : ($data['schedule_interval_minutes'] ?? null),
            'schedule_run_at' => $scheduleRunAt,
            'reserve_amount' => 0,
            'sweep_amount_source' => ($data['sweep_amount_source'] ?? FireblocksSweepRule::SWEEP_AMOUNT_SOURCE_AVAILABLE)
                === FireblocksSweepRule::SWEEP_AMOUNT_SOURCE_MAX_SPENDABLE
                    ? FireblocksSweepRule::SWEEP_AMOUNT_SOURCE_MAX_SPENDABLE
                    : FireblocksSweepRule::SWEEP_AMOUNT_SOURCE_AVAILABLE,
            'trigger_modes' => implode(',', $triggerModes),
            'deposit_delay_minutes' => $data['deposit_delay_minutes'] ?? 0,
            'sweep_orphan_deposits' => in_array('deposit', $triggerModes, true) && $request->has('sweep_orphan_deposits'),
            'fee_level' => strtoupper((string) ($data['fee_level'] ?? 'LOW')),
            'skip_if_fee_high' => false,
            'sweep_tokens_before_base' => false,
            'dry_run' => $request->boolean('dry_run'),
        ];
    }

    protected function assetCatalogue(): array
    {
        return SweepAssetMetadata::catalogue();
    }

    protected function formatTriggerModes(?string $modes): string
    {
        $list = array_filter(array_map('trim', explode(',', strtolower((string) $modes))));
        $labels = [];
        if (in_array('deposit', $list, true)) {
            $labels[] = 'Deposit';
        }
        if (in_array('scheduled', $list, true)) {
            $labels[] = 'Schedule';
        }

        return $labels !== [] ? implode(' + ', $labels) : '—';
    }

    protected function newRuleTemplate(): FireblocksSweepRule
    {
        $moduleConfig = SweepConfigService::tryGet() ?? [];

        return new FireblocksSweepRule([
            'enabled' => true,
            'priority' => 100,
            'source_type' => 'client_deposit_vaults',
            'trigger_modes' => 'deposit,scheduled',
            'fee_level' => 'LOW',
            'destination_vault_id' => $moduleConfig['default_omnibus_vault_id'] ?? '',
            'min_balance' => $moduleConfig['default_deposit_threshold'] ?? 0,
            'scheduled_min_balance' => $moduleConfig['default_scheduled_threshold'] ?? 0,
            'reserve_amount' => 0,
            'sweep_amount_source' => FireblocksSweepRule::SWEEP_AMOUNT_SOURCE_AVAILABLE,
            'use_gasless' => false,
            'treat_as_gross_amount' => false,
            'deposit_delay_minutes' => 0,
            'sweep_orphan_deposits' => false,
        ]);
    }
}
