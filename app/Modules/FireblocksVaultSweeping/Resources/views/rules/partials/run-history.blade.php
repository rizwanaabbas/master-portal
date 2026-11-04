@if (!empty($rule->exists))
@php
    $historyService = $runHistoryService ?? app(\App\Modules\FireblocksVaultSweeping\Services\SweepRuleRunHistoryService::class);
    $summary = $runHistorySummary ?? ($runHistory['summary'] ?? $historyService->summaryForRule($rule));
    $globalDryRun = !empty($moduleConfig['dry_run_mode']);
    $ruleDryRun = !empty($rule->dry_run);
    $canSubmitLive = ($sweepingActive ?? true) && !$globalDryRun && !$ruleDryRun;
    $preflight = $manualRunPreflight ?? $historyService->manualRunPreflight($rule);
    $runRedirect = $runRedirect ?? 'edit';
    $hideActionToolbar = !empty($hideActionToolbar);
@endphp

<div class="sweep-section mb-4" id="sweep-run-history">
    <div class="sweep-section-hdr">
        <span class="sweep-section-icon amber"><i class="fal fa-history"></i></span>
        <div class="flex-grow-1">
            <h5 class="mb-0 font-weight-bold">Run history & manual run</h5>
            <small class="text-muted">All sweep jobs for this rule — deposit webhooks, scheduled cron, and manual runs</small>
        </div>
        <div class="d-flex flex-wrap sweep-action-toolbar align-items-center ml-md-3 mt-2 mt-md-0">
            @unless ($hideActionToolbar)
            @if ($rule->enabled && $canSubmitLive)
                <form method="POST" action="{{ route('fireblocks.vault-sweeping.rules.run', $rule) }}" class="d-inline js-sweep-confirm-form"
                    data-sweep-confirm-type="manual-run"
                    data-sweep-rule-name="{{ $rule->name }}"
                    data-sweep-has-prior-run="{{ !empty($preflight['has_prior_run']) ? '1' : '0' }}"
                    data-sweep-prior-message="{{ e($preflight['message'] ?? '') }}"
                    data-sweep-in-flight="{{ !empty($preflight['in_flight']) ? '1' : '0' }}"
                    data-sweep-dry-run="{{ $rule->dry_run ? '1' : '0' }}">
                    @csrf
                    <input type="hidden" name="redirect" value="{{ $runRedirect }}">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fal fa-play mr-1"></i> Manual run (submit tx)
                    </button>
                </form>
            @elseif ($rule->enabled)
                <button type="button" class="btn btn-success btn-sm" disabled title="Enable sweeping and disable dry run">
                    <i class="fal fa-play mr-1"></i> Manual run
                </button>
            @else
                <span class="badge badge-warning align-self-center">Rule disabled — manual run unavailable</span>
            @endif
            @if ($rule->enabled)
            <a href="{{ route('fireblocks.vault-sweeping.rules.dry-run', $rule) }}" class="btn btn-outline-info btn-sm">
                <i class="fal fa-flask mr-1"></i> Dry run preview
            </a>
            @endif
            @endunless
        </div>
    </div>
    <div class="sweep-section-body">
        @if (!$canSubmitLive)
            <div class="alert alert-warning py-2 mb-3">
                <small>
                    <strong>Live submit disabled:</strong>
                    @if (!($sweepingActive ?? true))
                        sweeping module inactive.
                    @elseif ($globalDryRun)
                        global dry run is ON in module config.
                    @elseif ($ruleDryRun)
                        this rule has dry run enabled — uncheck in Safety section above.
                    @endif
                    Manual run will only log jobs until dry run is off.
                </small>
            </div>
        @else
            <div class="alert alert-light border py-2 mb-3">
                <small><i class="fal fa-info-circle text-primary mr-1"></i>
                    <strong>Deposit webhook</strong> jobs appear here when an Approved deposit triggers this rule.
                    Manual run scans tagged CI vaults and submits a <strong>new</strong> Fireblocks transaction for each qualifying pair.
                </small>
            </div>
        @endif

        @if (($summary['total'] ?? 0) === 0)
            <p class="text-muted mb-0">No sweep jobs yet for this rule.</p>
        @else
            <div class="row mb-3">
                <div class="col-md-3 col-6 mb-2">
                    <div class="sweep-stat-card">
                        <div class="label">Last run</div>
                        <div class="value"><small>{{ $summary['last_run_at'] ?? '—' }}</small></div>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <div class="sweep-stat-card">
                        <div class="label">Submitted</div>
                        <div class="value text-success">{{ $summary['submitted'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <div class="sweep-stat-card">
                        <div class="label">Skipped</div>
                        <div class="value">{{ $summary['skipped'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-md-2 col-6 mb-2">
                    <div class="sweep-stat-card">
                        <div class="label">Failed</div>
                        <div class="value text-danger">{{ $summary['failed'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <div class="sweep-stat-card">
                        <div class="label">Total jobs</div>
                        <div class="value">{{ $summary['total'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            @php $byTrigger = $summary['by_trigger'] ?? []; @endphp
            @if (($byTrigger['deposit'] ?? 0) > 0 || ($byTrigger['scheduled'] ?? 0) > 0 || ($byTrigger['manual'] ?? 0) > 0)
                <div class="d-flex flex-wrap gap-2 mb-3" style="gap: .5rem;">
                    @if (($byTrigger['deposit'] ?? 0) > 0)
                        <span class="badge badge-primary">Deposit webhook: {{ $byTrigger['deposit'] }}</span>
                    @endif
                    @if (($byTrigger['scheduled'] ?? 0) > 0)
                        <span class="badge badge-info">Scheduled: {{ $byTrigger['scheduled'] }}</span>
                    @endif
                    @if (($byTrigger['manual'] ?? 0) > 0)
                        <span class="badge badge-dark">Manual: {{ $byTrigger['manual'] }}</span>
                    @endif
                </div>
            @endif

            <div class="sweep-datagrid-wrap">
                <table class="table table-sm table-bordered sweep-datagrid w-100 mb-0" id="sweep-rule-jobs-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Trigger</th>
                            <th>Deposit tx</th>
                            <th>Source vault</th>
                            <th>Asset</th>
                            <th>Status</th>
                            <th>FB status</th>
                            <th>Status updated</th>
                            <th>Amount</th>
                            <th class="sweep-cell-wrap">Details</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endif
    </div>
</div>

@endif

