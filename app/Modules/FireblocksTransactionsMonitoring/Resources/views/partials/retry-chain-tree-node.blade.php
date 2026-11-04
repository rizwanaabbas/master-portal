{{-- Single node card in the resubmit / retry chain tree. --}}
@php
    $entry = $entry ?? ($node['entry'] ?? []);
    $isRoot = !empty($node['is_root']);
    $cardClasses = ['fb-retry-node-card'];
    if (!empty($entry['is_current'])) {
        $cardClasses[] = 'is-current';
    }
    if (!empty($entry['is_failed'])) {
        $cardClasses[] = 'is-failed';
    }
    if ($isRoot) {
        $cardClasses[] = 'is-root';
    }
@endphp
<div class="{{ implode(' ', $cardClasses) }}">
    <div class="fb-retry-node-role">
        {{ $isRoot ? 'Original withdrawal' : 'Retry attempt' }}
        @if (!empty($entry['is_current']))
            · viewing
        @endif
    </div>
    <div class="fb-retry-node-id">
        <code class="fb-copy" data-copy="{{ $entry['fireblocks_tx_id'] ?? '' }}">{{ $entry['fireblocks_tx_id'] ?? '—' }}</code>
    </div>
    <div class="fb-retry-node-meta">
        <strong>{{ $entry['status_label'] ?? ($entry['status'] ?? '—') }}</strong>
        @if (!empty($entry['sub_status']))
            · <code>{{ $entry['sub_status'] }}</code>
        @endif
    </div>
    @if (!empty($entry['substatus_message']))
        <div class="fb-retry-node-meta"><small>{{ $entry['substatus_message'] }}</small></div>
    @endif
    <div class="fb-retry-node-meta">
        @if (!empty($entry['internal_status_badge']))
            <span class="badge {{ $entry['internal_status_badge']['class'] ?? 'badge-secondary' }}">{{ $entry['internal_status_badge']['label'] ?? '' }}</span>
        @endif
        @if (!empty($entry['monitoring_badge']))
            <span class="badge {{ $entry['monitoring_badge']['class'] ?? 'badge-secondary' }}">{{ $entry['monitoring_badge']['label'] ?? '' }}</span>
        @endif
    </div>
    <div class="fb-retry-node-meta"><small class="text-muted">{{ $entry['created_at'] ?? '—' }}</small></div>
    <div class="fb-retry-node-actions">
        @if (($layout ?? 'modal') === 'ledger')
            @if (!empty($entry['ledger_url']))
                <a href="{{ $entry['ledger_url'] }}" class="btn btn-xs btn-outline-primary" target="_blank">Ledger</a>
            @endif
            <a href="{{ $entry['dashboard_url'] ?? '#' }}" class="btn btn-xs btn-outline-secondary" target="_blank">Monitoring</a>
        @else
            <button type="button" class="btn btn-xs btn-outline-primary btn-chain-view" data-tx-id="{{ $entry['fireblocks_tx_id'] ?? '' }}">View</button>
        @endif
    </div>
</div>
