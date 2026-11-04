@php
    $preflight = $manualRunPreflight ?? ['has_prior_run' => false, 'in_flight' => false, 'message' => ''];
    $runRedirect = $runRedirect ?? 'show';
    $globalDryRun = !empty($moduleConfig['dry_run_mode']);
    $canSubmitLive = ($sweepingActive ?? true) && !$globalDryRun && !$rule->dry_run;
    $btnSize = $btnSize ?? 'md';
    $btnClass = $btnSize === 'lg' ? 'btn-lg' : ($btnSize === 'sm' ? 'btn-sm' : '');
@endphp

<div class="d-flex flex-wrap align-items-center sweep-rule-actions">
    <a href="{{ route('fireblocks.vault-sweeping.rules.edit', $rule) }}" class="btn btn-primary {{ $btnClass }} sweep-rule-action-item">
        <i class="fal fa-edit mr-1"></i> Edit rule
    </a>

    <a href="{{ route('fireblocks.vault-sweeping.rules.export', $rule) }}" class="btn btn-outline-secondary {{ $btnClass }} sweep-rule-action-item">
        <i class="fal fa-file-export mr-1"></i> Export JSON
    </a>

    @if ($rule->enabled)
        <a href="{{ route('fireblocks.vault-sweeping.rules.dry-run', $rule) }}" class="btn btn-outline-info {{ $btnClass }} sweep-rule-action-item">
            <i class="fal fa-flask mr-1"></i> Dry run preview
        </a>

        <form method="POST" action="{{ route('fireblocks.vault-sweeping.rules.run', $rule) }}" class="d-inline js-sweep-confirm-form sweep-rule-action-item"
            data-sweep-confirm-type="manual-run"
            data-sweep-rule-name="{{ $rule->name }}"
            data-sweep-has-prior-run="{{ !empty($preflight['has_prior_run']) ? '1' : '0' }}"
            data-sweep-prior-message="{{ e($preflight['message'] ?? '') }}"
            data-sweep-in-flight="{{ !empty($preflight['in_flight']) ? '1' : '0' }}"
            data-sweep-dry-run="{{ $rule->dry_run ? '1' : '0' }}">
            @csrf
            <input type="hidden" name="redirect" value="{{ $runRedirect }}">
            <button type="submit" class="btn btn-success {{ $btnClass }}" @if (!$canSubmitLive) disabled @endif
                title="{{ $canSubmitLive ? 'Manual run (uses dust threshold)' : 'Enable sweeping and disable dry run' }}">
                <i class="fal fa-play mr-1"></i> Manual run
            </button>
        </form>

        @if ($rule->dry_run)
            <span class="badge badge-secondary align-self-center sweep-rule-action-item">Rule dry run</span>
        @endif
    @else
        <span class="badge badge-warning align-self-center sweep-rule-action-item">Rule disabled</span>
    @endif

    <form method="POST" action="{{ route('fireblocks.vault-sweeping.rules.destroy', $rule) }}"
        class="d-inline js-sweep-confirm-form sweep-rule-action-item sweep-rule-delete-form"
        data-sweep-confirm-type="delete"
        data-sweep-confirm-variant="danger"
        data-sweep-confirm-title="Delete sweep rule"
        data-sweep-confirm-button="Archive rule"
        data-sweep-confirm-body="Archive rule &ldquo;{{ $rule->name }}&rdquo;? It will be soft-deleted and will no longer run on deposit, cron, or manual triggers. Existing sweep job history is kept.">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger {{ $btnClass }}">
            <i class="fal fa-trash-alt mr-1"></i> Delete rule
        </button>
    </form>
</div>
