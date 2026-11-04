{{-- Shared monitoring action buttons (dashboard modal + ledger). --}}
@php
    $actions = $availableActions ?? null;
    $actionBtn = function (string $key) use ($actions) {
        if ($actions === null) {
            return ['visible' => true, 'enabled' => true, 'disabled_reason' => null];
        }

        return $actions[$key] ?? ['visible' => false, 'enabled' => false, 'disabled_reason' => null];
    };
    $sync = $actionBtn('sync');
    $boost = $actionBtn('boost');
    $drop = $actionBtn('drop');
    $resubmit = $actionBtn('resubmit');
    $responseId = $responseElementId ?? ('fb-action-response-' . md5($monitoringTxId ?? 'modal'));
    $wrapperClass = trim('fb-monitoring-actions ' . ($wrapperClass ?? 'mb-3'));
@endphp

@if (!empty($showMonitoringActions) && !empty($monitoringTxId))
    <div class="{{ $wrapperClass }}" data-tx-id="{{ $monitoringTxId }}" id="{{ $wrapperId ?? '' }}">
        @if (!empty($showHeading))
            <h6 class="text-muted text-uppercase mb-2">Monitoring Actions</h6>
        @endif
        <div id="{{ $responseId }}" class="mb-2 d-none fb-action-response"></div>
        <div class="btn-group btn-group-sm flex-wrap fb-monitoring-action-buttons">
            <button type="button"
                class="btn btn-outline-info fb-btn-sync {{ $sync['visible'] ? '' : 'd-none' }}"
                data-tx-id="{{ $monitoringTxId }}"
                @if(!$sync['enabled']) disabled title="{{ $sync['disabled_reason'] ?? '' }}" @endif>
                <i class="fal fa-sync"></i> Sync
            </button>
            <button type="button"
                class="btn btn-outline-success fb-btn-boost {{ $boost['visible'] ? '' : 'd-none' }}"
                data-tx-id="{{ $monitoringTxId }}"
                @if(!$boost['enabled']) disabled title="{{ $boost['disabled_reason'] ?? '' }}" @endif>
                <i class="fal fa-rocket"></i> Boost
            </button>
            <button type="button"
                class="btn btn-outline-warning fb-btn-drop {{ $drop['visible'] ? '' : 'd-none' }}"
                data-tx-id="{{ $monitoringTxId }}"
                @if(!$drop['enabled']) disabled title="{{ $drop['disabled_reason'] ?? '' }}" @endif>
                <i class="fal fa-times"></i> Drop
            </button>
            <button type="button"
                class="btn btn-outline-dark fb-btn-resubmit {{ $resubmit['visible'] ? '' : 'd-none' }}"
                data-tx-id="{{ $monitoringTxId }}"
                @if(!$resubmit['enabled']) disabled title="{{ $resubmit['disabled_reason'] ?? '' }}" @endif>
                <i class="fal fa-redo"></i> Resubmit
            </button>
        </div>
    </div>
@endif
