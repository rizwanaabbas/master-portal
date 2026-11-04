<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Support\SweepAssetMetadata;
use App\Modules\FireblocksVaultSweeping\Support\SweepDestination;

class SweepRuleFlowDiagramService
{
    public function build(
        FireblocksSweepRule $rule,
        ?array $moduleConfig = null,
        ?string $scheduleDescription = null
    ): array {
        $moduleConfig = $moduleConfig ?? SweepConfigService::tryGet() ?? [];
        $scheduleDescription = $scheduleDescription ?? app(SweepScheduleService::class)->describeSchedule($rule, $moduleConfig);

        $assetId = trim((string) ($rule->asset_ids ?? ''));
        $targetAssetId = $rule->targetAssetId() ?? $assetId;
        $destinationVaultId = SweepDestination::resolveVaultId($rule, $moduleConfig);
        $destinationLabel = SweepDestination::isPlaceholderVaultId($destinationVaultId)
            ? 'Module default omnibus'
            : 'Vault ' . $destinationVaultId;

        $sourceLabel = $this->describeSourceScope($rule, $moduleConfig);
        $assetMeta = $assetId !== '' ? SweepAssetMetadata::describe(null, $assetId) : [];
        $networkLabel = trim((string) ($assetMeta['network_name'] ?? '')) ?: null;

        $triggers = $this->triggerNodes($rule, $scheduleDescription);
        $checks = $this->checkNodes($rule);
        $gas = $this->gasNodes($rule, $assetId, $moduleConfig);
        $execution = $this->executionNodes($rule);

        $stats = $this->statCards($rule, $assetId, $targetAssetId, $destinationLabel, $scheduleDescription);

        return [
            'stats' => $stats,
            'lanes' => [
                ['key' => 'trigger', 'title' => 'When', 'icon' => 'fa-bolt', 'tone' => 'amber', 'nodes' => $triggers],
                ['key' => 'source', 'title' => 'From', 'icon' => 'fa-inbox-in', 'tone' => 'blue', 'nodes' => [
                    ['label' => 'Source vaults', 'detail' => $sourceLabel],
                    ['label' => 'Asset', 'detail' => $assetId !== '' ? $assetId : '—', 'badge' => $networkLabel],
                ]],
                ['key' => 'checks', 'title' => 'Checks', 'icon' => 'fa-filter', 'tone' => 'purple', 'nodes' => $checks],
                ['key' => 'gas', 'title' => 'Gas & fees', 'icon' => 'fa-gas-pump', 'tone' => 'amber', 'nodes' => $gas],
                ['key' => 'execute', 'title' => 'Execute', 'icon' => 'fa-play', 'tone' => 'green', 'nodes' => $execution],
                ['key' => 'destination', 'title' => 'To', 'icon' => 'fa-arrow-to-right', 'tone' => 'blue', 'nodes' => [
                    ['label' => 'Destination', 'detail' => $destinationLabel],
                    ['label' => 'Target asset', 'detail' => $targetAssetId !== '' ? $targetAssetId : '—'],
                ]],
            ],
        ];
    }

    /**
     * @return list<array{label: string, detail: string, badge?: string|null}>
     */
    protected function triggerNodes(FireblocksSweepRule $rule, string $scheduleDescription): array
    {
        $nodes = [];

        if ($rule->supportsTrigger('deposit')) {
            $detail = 'Approved deposit webhook';
            if ((int) $rule->deposit_delay_minutes > 0) {
                $detail .= ' · delay ' . (int) $rule->deposit_delay_minutes . ' min';
            }
            if ($rule->sweepsOrphanDeposits()) {
                $detail .= ' · includes orphan deposits';
            } else {
                $detail .= ' · ledger-linked only';
            }

            $nodes[] = [
                'label' => 'Deposit trigger',
                'detail' => $detail,
                'badge' => '≥ ' . $this->formatAmount($rule->min_balance),
            ];
        }

        if ($rule->supportsTrigger('scheduled')) {
            $nodes[] = [
                'label' => 'Scheduled trigger',
                'detail' => $scheduleDescription,
                'badge' => 'dust ≥ ' . $this->formatAmount($rule->scheduled_min_balance ?? 0),
            ];
        }

        if ($nodes === []) {
            $nodes[] = ['label' => 'No triggers', 'detail' => 'Rule will not run automatically'];
        }

        return $nodes;
    }

    /**
     * @return list<array{label: string, detail: string, badge?: string|null}>
     */
    protected function checkNodes(FireblocksSweepRule $rule): array
    {
        $nodes = [
            [
                'label' => 'Priority',
                'detail' => 'Lower number runs first among matching rules',
                'badge' => (string) (int) $rule->priority,
            ],
            [
                'label' => 'Balance basis',
                'detail' => $rule->sweepAmountSourceLabel(),
            ],
        ];

        if (!$rule->enabled) {
            $nodes[] = ['label' => 'Rule disabled', 'detail' => 'Evaluation skipped until enabled'];
        }

        if ($rule->dry_run) {
            $nodes[] = ['label' => 'Dry run', 'detail' => 'Simulates sweep — no Fireblocks submit'];
        }

        return $nodes;
    }

    /**
     * @return list<array{label: string, detail: string, badge?: string|null}>
     */
    protected function gasNodes(FireblocksSweepRule $rule, string $assetId, array $moduleConfig): array
    {
        $assetMeta = $assetId !== '' ? SweepAssetMetadata::describe(null, $assetId) : [];

        $nodes = [
            [
                'label' => 'Fee level',
                'detail' => 'Fireblocks estimate_fee + create transaction',
                'badge' => $rule->feeLevel(),
            ],
        ];

        if ($rule->shouldUseGasless($assetId)) {
            $nodes[] = [
                'label' => 'Gas relay',
                'detail' => 'ERC-20 sweep uses useGasless — no native ETH on source',
                'badge' => 'ON',
            ];
        } elseif ($rule->shouldTreatAsGrossAmount($assetId)) {
            $nodes[] = [
                'label' => 'Gross amount',
                'detail' => 'Native sweep uses treatAsGrossAmount — fee deducted from amount',
                'badge' => 'ON',
            ];
        } elseif (($assetMeta['gas_funding_mode'] ?? '') === 'tron_station') {
            $feedVault = trim((string) ($rule->gas_funding_vault_id ?? ''));
            $feedAmount = $rule->feed_trx_amount;
            $nodes[] = [
                'label' => 'TRX gas feed',
                'detail' => $feedVault !== ''
                    ? 'Prefund TRX from vault ' . $feedVault
                    : 'Uses module gas funding vault',
                'badge' => $feedAmount !== null ? $this->formatAmount($feedAmount) . ' TRX' : 'default',
            ];
        } else {
            $nodes[] = [
                'label' => 'Native gas',
                'detail' => 'Source vault must hold network fee token',
            ];
        }

        return $nodes;
    }

    /**
     * @return list<array{label: string, detail: string, badge?: string|null}>
     */
    protected function executionNodes(FireblocksSweepRule $rule): array
    {
        if ($rule->dry_run) {
            return [
                ['label' => 'Preview only', 'detail' => 'Logs intended sweep amount and destination'],
            ];
        }

        return [
            ['label' => 'Submit sweep', 'detail' => 'Fireblocks vault-to-vault transfer'],
            ['label' => 'Track status', 'detail' => 'Webhook updates job until terminal state'],
        ];
    }

    /**
     * @return list<array{label: string, value: string, tone?: string}>
     */
    protected function statCards(
        FireblocksSweepRule $rule,
        string $assetId,
        string $targetAssetId,
        string $destinationLabel,
        string $scheduleDescription
    ): array {
        $statusValue = $rule->enabled ? 'Active' : 'Disabled';
        if ($rule->dry_run) {
            $statusValue .= ' · Dry run';
        }

        $triggerParts = [];
        if ($rule->supportsTrigger('deposit')) {
            $triggerParts[] = 'Deposit';
        }
        if ($rule->supportsTrigger('scheduled')) {
            $triggerParts[] = 'Schedule';
        }

        $stats = [
            ['label' => 'Rule', 'value' => '#' . $rule->id, 'tone' => 'slate'],
            ['label' => 'Status', 'value' => $statusValue, 'tone' => $rule->enabled ? 'green' : 'slate'],
            ['label' => 'Priority', 'value' => (string) (int) $rule->priority, 'tone' => 'purple'],
            ['label' => 'Triggers', 'value' => $triggerParts !== [] ? implode(' + ', $triggerParts) : '—', 'tone' => 'amber'],
        ];

        if ($rule->supportsTrigger('deposit')) {
            $stats[] = [
                'label' => 'Orphan deposits',
                'value' => $rule->sweepsOrphanDeposits() ? 'Yes' : 'No',
                'tone' => $rule->sweepsOrphanDeposits() ? 'green' : 'slate',
            ];
        }

        return array_merge($stats, [
            ['label' => 'Asset', 'value' => $assetId !== '' ? $assetId : '—', 'tone' => 'blue'],
            ['label' => 'Destination', 'value' => $destinationLabel, 'tone' => 'blue'],
            ['label' => 'Target asset', 'value' => $targetAssetId !== '' ? $targetAssetId : '—', 'tone' => 'blue'],
            ['label' => 'Schedule', 'value' => $rule->supportsTrigger('scheduled') ? $scheduleDescription : '—', 'tone' => 'amber'],
        ]);
    }

    protected function describeSourceScope(FireblocksSweepRule $rule, array $moduleConfig): string
    {
        return match ($rule->source_type) {
            'vault_id_list' => 'Vault IDs: ' . (trim((string) $rule->source_vault_ids) ?: '—'),
            'vault_name_prefix' => 'Vault name prefix: "' . trim((string) $rule->source_vault_ids) . '"',
            default => 'All client deposit vaults (Client Intermediary tag ID: '
                . ($this->clientIntermediaryTagId($moduleConfig) ?: 'not configured')
                . ')',
        };
    }

    protected function clientIntermediaryTagId(array $moduleConfig): string
    {
        $raw = $moduleConfig['client_intermediary_tag_id'] ?? '';
        if (is_array($raw)) {
            return trim((string) ($raw['value'] ?? ''));
        }

        return trim((string) $raw);
    }

    protected function formatAmount(mixed $value): string
    {
        if (!is_numeric($value)) {
            return '0';
        }

        $formatted = rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
