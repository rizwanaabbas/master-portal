@if (!empty($fireblocksMonitoring['enabled']))
    @include('fireblocks-monitoring::partials.monitoring-actions-script', [
        'defaultFeeLevel' => $fireblocksMonitoring['config']['boost_fee_level'] ?? 'HIGH',
    ])
    <script>window.fbMonitoringReloadLedger = function () { window.location.reload(); };</script>
@endif
@if (!empty($fireblocksMonitoring['transaction']))
    @include('fireblocks-monitoring::partials.detail-field-hint-script')
    <script>
    @include('crypto-management.crypto-transactions.partials.fireblocks-beneficiary-modal-script')
    </script>
@endif
