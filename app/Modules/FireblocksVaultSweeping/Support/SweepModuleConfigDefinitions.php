<?php

namespace App\Modules\FireblocksVaultSweeping\Support;

class SweepModuleConfigDefinitions
{
    public static function doc(string $key): string
    {
        return self::docs()[$key] ?? 'https://developers.fireblocks.com/docs/sweep-funds';
    }

    public static function docs(): array
    {
        return [
            'sweep' => 'https://developers.fireblocks.com/docs/sweep-funds',
            'create_tx' => 'https://developers.fireblocks.com/reference/create-transactions',
            'vaults' => 'https://developers.fireblocks.com/reference/get-vault-account-asset',
        ];
    }

    public static function all(): array
    {
        return [
            'sweeping_enabled' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Sweeping Enabled',
                'hint' => 'Master switch. When off, no deposit or scheduled sweeps run.',
                'usage' => 'SweepConfigService::isActive(), WebhookService deposit sweep dispatch, fireblocks:run-sweeps scheduler.',
                'doc_url' => self::doc('sweep'),
                'doc_label' => 'Fireblocks sweep documentation',
            ],
            'default_omnibus_vault_id' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Omnibus Vault ID',
                'hint' => 'Fireblocks vault ID that receives swept funds from client deposit vaults.',
                'usage' => 'SweepExecutionService destination when rule destination is omnibus; rule form default.',
                'doc_url' => self::doc('vaults'),
            ],
            'scheduled_interval_minutes' => [
                'value' => '5',
                'type' => 'number',
                'label' => 'Dust Cleanup Scan Interval (minutes)',
                'hint' => 'How often the scheduled job scans tagged CI vaults (includeTagIds) for dust cleanup. Uses cursor paging — 500 vaults per API page.',
                'usage' => 'Console Kernel schedule for fireblocks:run-sweeps scheduled/dust path.',
                'doc_url' => self::doc('sweep'),
            ],
            'max_sweeps_per_run' => [
                'value' => '50',
                'type' => 'number',
                'label' => 'Max Sweeps Per Scheduled Run',
                'hint' => 'Cap on sweep transactions submitted in one scheduled run.',
                'usage' => 'RunScheduledSweepsCommand / sweep runner batch limit.',
                'doc_url' => self::doc('sweep'),
            ],
            'dry_run_mode' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Global Dry Run Mode',
                'hint' => 'Evaluate rules and log jobs only — no Fireblocks transactions.',
                'usage' => 'SweepExecutionService — skips POST /transactions when enabled.',
                'doc_url' => self::doc('sweep'),
            ],
            'default_deposit_threshold' => [
                'value' => '0',
                'type' => 'text',
                'label' => 'Default Deposit Threshold (asset units)',
                'hint' => 'Fallback when a rule leaves deposit threshold at 0. Compared against Fireblocks available balance (spendable now) — not total, pending, or locked.',
                'sample' => '100 USDT or 0.01 ETH',
                'usage' => 'EvaluateDepositSweepJob / rule matching when rule deposit threshold is 0.',
                'doc_url' => self::doc('sweep'),
            ],
            'default_scheduled_threshold' => [
                'value' => '0',
                'type' => 'text',
                'label' => 'Default Dust Cleanup Threshold (asset units)',
                'hint' => 'Fallback for scheduled/manual sweeps when a rule leaves dust threshold at 0. Usually 0. Uses available balance only — we do not use Fireblocks minAmountThreshold (that filters on total).',
                'sample' => '0',
                'usage' => 'Scheduled vault scan sweeps when rule scheduled_min_balance is 0.',
                'doc_url' => self::doc('sweep'),
            ],
            'default_tron_station_vault_id' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Default TRON Station Vault ID',
                'hint' => 'Fireblocks vault that funds TRX gas into client deposit vaults before TRC-20 token sweeps.',
                'sample' => 'Vault ID of TRON Station',
                'usage' => 'TronGasFundingService — gas feed source vault when rule does not override.',
                'doc_url' => self::doc('vaults'),
            ],
            'client_intermediary_tag_id' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Client Intermediary Vault Tag ID',
                'hint' => 'Fireblocks tag UUID for Client Intermediary vaults. Sweeps scan GET /vault/accounts_paged?includeTagIds=…&limit=500 with cursor paging (after=). New vaults get this tag at creation — untagged vaults are invisible to sweeps.',
                'sample' => '33c56eac-601a-47f2-b265-bee94c4c4246 (testnet CI standard tag)',
                'usage' => 'Scheduled sweep vault paging; vault tagging on client vault creation.',
                'doc_url' => self::doc('vaults'),
            ],
        ];
    }

    public static function mergeInto(array $existing): array
    {
        $definitions = self::all();
        $merged = [];

        foreach ($definitions as $key => $definition) {
            if (isset($existing[$key])) {
                $merged[$key] = array_merge($definition, [
                    'value' => $existing[$key]['value'] ?? $definition['value'],
                ]);
            } else {
                $merged[$key] = $definition;
            }
        }

        foreach ($existing as $key => $field) {
            if (!isset($merged[$key])) {
                $merged[$key] = $field;
            }
        }

        return $merged;
    }
}
