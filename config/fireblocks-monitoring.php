<?php

return [
    /*
    | Chainalysis KYT console base URL (optional deep links for transaction AML scans).
    */
    'chainalysis_kyt_portal_url' => env('CHAINALYSIS_KYT_PORTAL_URL', 'https://kyt.chainalysis.com'),

    /*
    | Optional Fireblocks console transaction URL prefix, e.g. https://console.fireblocks.io/v2/transactions
    */
    'fireblocks_console_tx_url' => env('FIREBLOCKS_CONSOLE_TX_URL', ''),
];
