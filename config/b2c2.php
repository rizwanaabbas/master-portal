<?php

return [
    'base_url'          => env('B2C2_BASE_URL', 'https://api.b2c2.com'),
    'api_token'         => env('B2C2_API_TOKEN', ''),
    'timeout'           => env('B2C2_TIMEOUT', 30),
    'connect_timeout'   => env('B2C2_CONNECT_TIMEOUT', 10),
    'max_retries'       => 3,
    'retry_delay'       => 500,
    'debug'             => env('B2C2_DEBUG', false),
    'proxy'             => null,
    'headers'           => [],
];
