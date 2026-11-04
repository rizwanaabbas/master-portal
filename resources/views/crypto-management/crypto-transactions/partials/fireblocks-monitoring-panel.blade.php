{{--
    Ledger / pending page wrapper for shared Fireblocks monitoring detail.
    Expects $fireblocksMonitoring from MonitoringTransactionDetailService::buildForLedger().
    Pass $trn (account_transactions row) and/or $transactionRequest (TRRequest).
--}}
@php
    $ledgerRow = $trn ?? $transactionRequest ?? null;
@endphp
@if (!empty($fireblocksMonitoring) && !empty($fireblocksMonitoring['transaction']))
    @php
        $monitoringActive = $fireblocksMonitoring['monitoring_active'] ?? ($fireblocksMonitoring['enabled'] ?? false);
        $detail = $fireblocksMonitoring;
    @endphp

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-hdr">
                    <h2>
                        Transaction Monitoring
                        @if ($monitoringActive)
                            <span class="badge badge-success ml-2">Monitoring Active</span>
                        @else
                            <span class="badge badge-secondary ml-2">Monitoring Module Disabled</span>
                        @endif
                    </h2>
                </div>
                <div class="panel-container show">
                    <div class="panel-content">
                        @include('fireblocks-monitoring::partials.transaction-detail-content', [
                            'detail' => $detail,
                            'layout' => 'ledger',
                            'showActions' => $monitoringActive,
                            'showDashboardLink' => $monitoringActive,
                            'showLedgerLink' => false,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($monitoringActive)
        @include('fireblocks-monitoring::partials.monitoring-actions', [
            'defaultFeeLevel' => $fireblocksMonitoring['config']['boost_fee_level'] ?? 'HIGH',
            'defaultGasPrice' => $fireblocksMonitoring['config']['boost_gas_price'] ?? '',
        ])
    @endif
@elseif (!empty($fireblocksMonitoring['missing_record']) && $ledgerRow)
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-hdr">
                    <h2>Transaction Monitoring <span class="badge badge-secondary ml-2">Pending sync</span></h2>
                </div>
                <div class="panel-container show">
                    <div class="panel-content">
                        <div class="alert alert-info mb-0">
                            <i class="fal fa-info-circle"></i>
                            Transaction ID
                            <code>{{ $fireblocksMonitoring['crypto_transaction_id'] ?? $ledgerRow->crypto_transaction_id ?? '—' }}</code>
                            is a Fireblocks custody transfer, but no monitoring record was found in the portal yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@include('fireblocks-monitoring::partials.retry-chain-tree-styles')
@include('crypto-management.crypto-transactions.partials.fireblocks-beneficiary-modal')
