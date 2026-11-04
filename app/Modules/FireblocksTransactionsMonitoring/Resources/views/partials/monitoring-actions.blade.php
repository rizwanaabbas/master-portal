@include('fireblocks-monitoring::partials.monitoring-actions-modals', [
    'defaultFeeLevel' => $defaultFeeLevel ?? 'HIGH',
    'defaultGasPrice' => $defaultGasPrice ?? '',
])
