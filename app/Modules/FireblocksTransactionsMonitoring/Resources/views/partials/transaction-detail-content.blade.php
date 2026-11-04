{{--
    Shared Fireblocks monitoring transaction detail (dashboard modal + ledger panel).
    Expects $detail from MonitoringTransactionDetailService::build().
--}}
@php
    use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
    use App\Modules\FireblocksTransactionsMonitoring\Support\MonitoringTransactionDetailFieldHints;

    $fb = $detail['transaction'];
    $layout = $layout ?? 'inline';
    $showActions = $showActions ?? !empty($detail['monitoring_active']);
    $showDashboardLink = $showDashboardLink ?? ($layout === 'ledger');
    $showLedgerLink = $showLedgerLink ?? ($layout === 'modal' && !empty($detail['ledger_url']));
    $queue = $detail['queue_status'] ?? [];
    $dashCols = $detail['dashboard_columns'] ?? [];
    $fieldHints = MonitoringTransactionDetailFieldHints::all();
@endphp

@if ($showActions)
    @include('fireblocks-monitoring::partials.monitoring-action-buttons', [
        'showMonitoringActions' => true,
        'monitoringTxId' => $fb->fireblocks_tx_id,
        'availableActions' => $detail['available_actions'] ?? null,
        'showHeading' => true,
        'wrapperId' => $layout === 'modal' ? 'tx-detail-actions-wrap' : null,
        'responseElementId' => $layout === 'modal' ? 'tx-action-response' : null,
    ])
@endif

@if ($showDashboardLink || $showLedgerLink)
    <div class="mb-3 d-flex flex-wrap">
        @if ($showDashboardLink)
            <a href="{{ $detail['dashboard_url'] ?? '#' }}" class="btn btn-sm btn-outline-primary mr-2 mb-2" target="_blank">
                <i class="fal fa-external-link"></i> Open Monitoring Dashboard
            </a>
        @endif
        @if ($showLedgerLink)
            <a href="{{ $detail['ledger_url'] }}" class="btn btn-sm btn-outline-secondary mb-2" target="_blank">
                <i class="fal fa-external-link"></i> Open Ledger
            </a>
        @endif
    </div>
@endif

<div class="row fb-monitoring-detail">
    <div class="col-md-6">
        <h6 class="text-muted text-uppercase mb-2">Transaction</h6>
        <table class="table table-sm table-striped table-bordered mb-0">
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Transaction ID',
                    'hint' => $fieldHints['fireblocks_tx_id'],
                    'width' => '40%',
                ])
                <td><code class="fb-copy" data-copy="{{ $fb->fireblocks_tx_id }}">{{ $fb->fireblocks_tx_id }}</code></td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Asset / Amount',
                    'hint' => $fieldHints['asset_amount'],
                ])
                <td>{!! $detail['amount_fmt'] ?? e($fb->amount . ' ' . $fb->asset_id) !!}</td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Direction',
                    'hint' => $fieldHints['direction'],
                ])
                <td>{{ $fb->direction ?: '—' }}</td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Initiator',
                    'hint' => $fieldHints['initiator'],
                ])
                <td>{!! $detail['actor_badges']['initiator'] ?? e($detail['actors']['initiator'] ?? '—') !!}</td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'TAP Approvers',
                    'hint' => $fieldHints['tap_approvers'],
                ])
                <td>{!! $detail['actor_badges']['approvers'] ?? e($detail['actors']['approvers'] ?? '—') !!}</td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Signers',
                    'hint' => $fieldHints['signers'],
                ])
                <td>{!! $detail['actor_badges']['signers'] ?? e($detail['actors']['signers'] ?? '—') !!}</td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Primary Status',
                    'hint' => $fieldHints['primary_status'],
                ])
                <td>
                    @if (!empty($detail['primary_status_badge']))
                        <span class="badge {{ $detail['primary_status_badge']['class'] }}">{{ $detail['primary_status_badge']['label'] }}</span>
                    @else
                        {{ $detail['primary_status_label'] ?? '' }}
                    @endif
                    <small class="text-muted">({{ $detail['fireblocks_primary_status'] ?? $fb->status }})</small>
                </td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Sub-status',
                    'hint' => $fieldHints['sub_status'],
                ])
                <td>
                    @if (!empty($detail['fireblocks_sub_status']) || $fb->sub_status)
                        @php
                            $subStatusCode = $detail['fireblocks_sub_status'] ?? $fb->sub_status;
                            $subStatusBadge = FireblocksStatusHelper::substatusBadge($subStatusCode);
                        @endphp
                        <div><span class="{{ $subStatusBadge['class'] }}">{{ $subStatusBadge['label'] }}</span></div>
                        @if (!empty($detail['substatus_message']))
                            <small>{{ $detail['substatus_message'] }}</small>
                        @endif
                        @if (!empty($detail['substatus_category']) && $detail['substatus_category'] !== 'Other')
                            <div><span class="badge badge-light">{{ $detail['substatus_category'] }}</span></div>
                        @endif
                    @else
                        —
                    @endif
                </td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Internal Status',
                    'hint' => $fieldHints['internal_status'],
                ])
                <td>
                    <span class="badge {{ $detail['internal_status_badge']['class'] ?? 'badge-secondary' }}">
                        {{ $detail['internal_status_badge']['label'] ?? '—' }}
                    </span>
                    @if (!empty($detail['custody_balance_pending']) && !empty($detail['pending_reason']))
                        <div class="text-warning small mt-2">{{ $detail['pending_reason'] }}</div>
                    @endif
                </td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'TX Hash',
                    'hint' => $fieldHints['tx_hash'],
                ])
                <td>
                    @if ($fb->tx_hash)
                        <code class="fb-copy" data-copy="{{ $fb->tx_hash }}">{{ $fb->tx_hash }}</code>
                    @else
                        —
                    @endif
                </td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Vault Account',
                    'hint' => $fieldHints['vault_account'],
                ])
                <td>{{ $fb->source_vault_account_id ?: '—' }}</td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Destination',
                    'hint' => $fieldHints['destination'],
                ])
                <td>{{ $fb->destination_address ?: '—' }}</td>
            </tr>
            @if (strtolower((string) ($fb->direction ?? '')) === 'outgoing')
                <tr>
                    @include('fireblocks-monitoring::partials.detail-field-th', [
                        'label' => 'Beneficiary',
                        'hint' => $fieldHints['beneficiary'],
                    ])
                    <td>
                        @include('crypto-management.crypto-transactions.partials.fireblocks-beneficiary-cell', [
                            'fireblocksCryptoBeneficiary' => $detail['fireblocks_crypto_beneficiary'] ?? null,
                        ])
                    </td>
                </tr>
            @endif
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'External TX ID',
                    'hint' => $fieldHints['external_tx_id'],
                ])
                <td>{{ $fb->external_tx_id ?: '—' }}</td>
            </tr>
            @php $chainalysis = $detail['chainalysis'] ?? null; @endphp
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Chainalysis / AML',
                    'hint' => $fieldHints['chainalysis'],
                ])
                <td>
                    @if (!empty($chainalysis['status']))
                        <span class="badge {{ $chainalysis['badge']['class'] ?? 'badge-secondary' }}">
                            {{ $chainalysis['badge']['label'] ?? $chainalysis['status'] }}
                        </span>
                        @if (!empty($chainalysis['address_screen']['flow']))
                            <small class="text-muted d-block">Screen: {{ str_replace('_', ' ', $chainalysis['address_screen']['flow']) }}</small>
                        @endif
                        @php
                            $kytEntityUrl = $chainalysis['links']['kyt_portal'] ?? $chainalysis['scan_url'] ?? null;
                        @endphp
                        @if (!empty($kytEntityUrl))
                            <a href="{{ $kytEntityUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-xs btn-outline-primary ml-1">
                                <i class="fal fa-external-link"></i> KYT Entity
                            </a>
                        @endif
                        @if (!empty($chainalysis['links']['kyt_transaction']))
                            <a href="{{ $chainalysis['links']['kyt_transaction'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-xs btn-outline-secondary ml-1">KYT Transaction</a>
                        @endif
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6 class="text-muted text-uppercase mb-2">Ledger &amp; ops</h6>
        <table class="table table-sm table-striped table-bordered mb-0">
            @if (!empty($dashCols['queue']))
                <tr>
                    @include('fireblocks-monitoring::partials.detail-field-th', [
                        'label' => 'Queue Status',
                        'hint' => $fieldHints['queue_status'],
                    ])
                    <td>
                        @if (!empty($queue['label']) && $queue['label'] !== '—')
                            <span class="badge badge-{{ $queue['class'] ?? 'secondary' }}">{{ $queue['label'] }}</span>
                            @if (!empty($queue['detail']))
                                <br><small>{{ $queue['detail'] }}</small>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endif
            @if (!empty($dashCols['ledger']))
                <tr>
                    @include('fireblocks-monitoring::partials.detail-field-th', [
                        'label' => 'Ledger Status',
                        'hint' => $fieldHints['ledger_status'],
                    ])
                    <td>
                        {{ $detail['ledger_status'] ?? '—' }}
                    </td>
                </tr>
            @endif
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Account',
                    'hint' => $fieldHints['account'],
                ])
                <td>
                    @if (!empty($detail['ledger_account']['title']))
                        <div>{{ $detail['ledger_account']['title'] }}</div>
                        <small class="text-muted">{{ $detail['ledger_account']['account_number'] }}</small>
                    @elseif (!empty($detail['account_label']))
                        {{ $detail['account_label'] }}
                    @else
                        —
                    @endif
                </td>
            </tr>
            @if (!empty($dashCols['stuck_for']))
                <tr>
                    @include('fireblocks-monitoring::partials.detail-field-th', [
                        'label' => 'Stuck For',
                        'hint' => $fieldHints['stuck_for'],
                    ])
                    <td>
                        {{ $detail['stuck_for'] ?? '—' }}
                        @if ($fb->stuck_detected_at)
                            <small class="text-muted d-block">Since {{ $fb->stuck_detected_at->format('Y-m-d H:i:s') }}</small>
                        @endif
                    </td>
                </tr>
            @endif
            @if (!empty($dashCols['reattempts']))
                <tr>
                    @include('fireblocks-monitoring::partials.detail-field-th', [
                        'label' => 'Reattempts',
                        'hint' => $fieldHints['reattempts'],
                    ])
                    <td>{{ $detail['reattempt_count'] ?? 0 }}</td>
                </tr>
            @endif
            @if (!empty($dashCols['last_reattempt']))
                <tr>
                    @include('fireblocks-monitoring::partials.detail-field-th', [
                        'label' => 'Last Reattempt',
                        'hint' => $fieldHints['last_reattempt'],
                    ])
                    <td>{{ $detail['last_reattempt_at'] ?? '—' }}</td>
                </tr>
            @endif
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Pending Action',
                    'hint' => $fieldHints['pending_action'],
                ])
                <td>{{ $fb->pending_action ?: '—' }}</td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Last Webhook',
                    'hint' => $fieldHints['last_webhook'],
                ])
                <td>{{ $fb->last_webhook_at ? $fb->last_webhook_at->format('Y-m-d H:i:s') : '—' }}</td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Last Polled',
                    'hint' => $fieldHints['last_polled'],
                ])
                <td>{{ $fb->last_polled_at ? $fb->last_polled_at->format('Y-m-d H:i:s') : '—' }}</td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Parent TX',
                    'hint' => $fieldHints['parent_tx'],
                ])
                <td>{{ $fb->parent_fireblocks_tx_id ?: '—' }}</td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Root TX',
                    'hint' => $fieldHints['root_tx'],
                ])
                <td>{{ $fb->root_fireblocks_tx_id ?: '—' }}</td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Replacement TX',
                    'hint' => $fieldHints['replacement_tx'],
                ])
                <td>{{ $fb->replaced_by_fireblocks_tx_id ?: '—' }}</td>
            </tr>
            <tr>
                @include('fireblocks-monitoring::partials.detail-field-th', [
                    'label' => 'Resolution',
                    'hint' => $fieldHints['resolution'],
                ])
                <td>{{ $detail['resolution']['message'] ?? '—' }}</td>
            </tr>
        </table>
    </div>
</div>

@include('fireblocks-monitoring::partials.retry-chain-panel', ['detail' => $detail, 'layout' => $layout])

@if (!empty($detail['alerts']) && count($detail['alerts']))
    <h6 class="text-muted text-uppercase mt-3 mb-2">Monitoring Alerts</h6>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead>
                <tr>
                    @include('fireblocks-monitoring::partials.detail-field-th', ['label' => 'Time', 'hint' => $fieldHints['alert_time']])
                    @include('fireblocks-monitoring::partials.detail-field-th', ['label' => 'Type', 'hint' => $fieldHints['alert_type']])
                    @include('fireblocks-monitoring::partials.detail-field-th', ['label' => 'Status', 'hint' => $fieldHints['alert_status']])
                    @include('fireblocks-monitoring::partials.detail-field-th', ['label' => 'Message', 'hint' => $fieldHints['alert_message']])
                </tr>
            </thead>
            <tbody>
                @foreach ($detail['alerts'] as $alert)
                    <tr>
                        <td>{{ $alert->created_at }}</td>
                        <td>{{ $alert->alert_type }}</td>
                        <td>{{ $alert->status }}</td>
                        <td>{{ $alert->message }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if (!empty($detail['logs']) && count($detail['logs']))
    <h6 class="text-muted text-uppercase mt-3 mb-2">Monitoring Action Log</h6>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead>
                <tr>
                    @include('fireblocks-monitoring::partials.detail-field-th', ['label' => 'Time', 'hint' => $fieldHints['log_time']])
                    @include('fireblocks-monitoring::partials.detail-field-th', ['label' => 'Action', 'hint' => $fieldHints['log_action']])
                    @include('fireblocks-monitoring::partials.detail-field-th', ['label' => 'Source', 'hint' => $fieldHints['log_source']])
                    @include('fireblocks-monitoring::partials.detail-field-th', ['label' => 'Success', 'hint' => $fieldHints['log_success']])
                    @include('fireblocks-monitoring::partials.detail-field-th', ['label' => 'Detail', 'hint' => $fieldHints['log_detail']])
                </tr>
            </thead>
            <tbody>
                @foreach ($detail['logs'] as $log)
                    <tr>
                        <td>{{ $log->created_at }}</td>
                        <td>{{ $log->action }}</td>
                        <td>{{ $log->trigger_source }}</td>
                        <td>{{ $log->success ? 'Yes' : 'No' }}</td>
                        <td>
                            @if (in_array($log->action, ['chainalysis_compliance', 'chainalysis_address_screen'], true))
                                @if ($log->error_message)
                                    <div class="text-danger small mb-1">{{ $log->error_message }}</div>
                                @endif
                                @if ($log->request_payload || $log->response_payload)
                                    <details class="small">
                                        <summary>Payload &amp; response</summary>
                                        @if ($log->request_payload)
                                            <div class="mt-1"><strong>Request</strong><pre class="mb-1 p-2 bg-light border rounded" style="max-height:160px;overflow:auto;">{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
                                        @endif
                                        @if ($log->response_payload)
                                            <div><strong>Response</strong><pre class="mb-0 p-2 bg-light border rounded" style="max-height:160px;overflow:auto;">{{ json_encode($log->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
                                        @endif
                                    </details>
                                @else
                                    —
                                @endif
                            @else
                                {{ $log->error_message ?: '—' }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
