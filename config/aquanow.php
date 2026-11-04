<?php

return [
    'base_url'          => env('AQUANOW_BASE_URL', 'https://api.aquanow.io'),
    'market_data_url'   => env('AQUANOW_MARKET_DATA_URL', 'https://api.aquanow.io'),
    'api_key'           => env('AQUANOW_API_KEY', ''),
    'api_secret'        => env('AQUANOW_API_SECRET', ''),
    'credit_account_id' => env('AQUANOW_CREDIT_ACCOUNT_ID', ''),
    'timeout'           => env('AQUANOW_TIMEOUT', 30),
    'connect_timeout'   => env('AQUANOW_CONNECT_TIMEOUT', 10),
    'max_retries'       => 3,
    'retry_delay'       => 500,
    'debug'             => env('AQUANOW_DEBUG', false),
    'proxy'             => null,
    'headers'           => [],
];
