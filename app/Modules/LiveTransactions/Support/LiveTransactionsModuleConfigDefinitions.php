<?php

namespace App\Modules\LiveTransactions\Support;

class LiveTransactionsModuleConfigDefinitions
{
    public static function all(): array
    {
        return [
            'tray_enabled' => [
                'value' => '1',
                'type' => 'checkbox',
                'label' => 'Enable Live Transactions Tray',
                'hint' => 'When enabled, permitted users see the sliding live transactions panel across the admin portal.',
            ],
            'max_records_per_page' => [
                'value' => '20',
                'type' => 'number',
                'label' => 'Max Records Per Page',
                'hint' => 'Maximum number of transaction cards shown in the tray at one time. Paging applies when more records exist.',
                'sample' => '20',
            ],
            'asset_filter' => [
                'value' => 'both',
                'type' => 'select',
                'label' => 'Asset Type Filter',
                'hint' => 'Control which transactions appear in the live tray: crypto only, fiat only, or both.',
                'options' => [
                    'both' => 'Show crypto and fiat',
                    'crypto_only' => 'Show only crypto',
                    'fiat_only' => 'Show only fiat',
                ],
            ],
            'direction_filter' => [
                'value' => 'both',
                'type' => 'select',
                'label' => 'Direction Filter',
                'hint' => 'Filter incoming deposits/credits, outgoing withdrawals/debits, or show both directions.',
                'options' => [
                    'both' => 'Show incoming and outgoing',
                    'incoming_only' => 'Show only incoming',
                    'outgoing_only' => 'Show only outgoing',
                ],
            ],
            'poll_interval_open_seconds' => [
                'value' => '3',
                'type' => 'number',
                'label' => 'Poll Interval When Tray Open (seconds)',
                'hint' => 'How often to check for new transactions and status updates while the tray is open.',
                'sample' => '3',
            ],
            'poll_interval_closed_seconds' => [
                'value' => '8',
                'type' => 'number',
                'label' => 'Poll Interval When Tray Closed (seconds)',
                'hint' => 'Background refresh interval for the LIVE tab badge when the tray is collapsed.',
                'sample' => '8',
            ],
        ];
    }

    public static function mergeInto(array $existing): array
    {
        foreach (self::all() as $key => $definition) {
            if (!array_key_exists($key, $existing)) {
                $existing[$key] = $definition;
            } else {
                foreach (['label', 'type', 'hint', 'sample', 'placeholder', 'options'] as $prop) {
                    if (isset($definition[$prop]) && empty($existing[$key][$prop])) {
                        $existing[$key][$prop] = $definition[$prop];
                    }
                }
            }
        }

        return $existing;
    }
}
