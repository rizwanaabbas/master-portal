<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Support\SweepMath;

class SweepDryRunService
{
    public function __construct(
        protected SweepEvaluationService $evaluation,
        protected SweepExecutionService $execution,
        protected SweepBalanceService $balances,
    ) {
    }

    public function previewBefore(FireblocksSweepRule $rule): array
    {
        return $this->buildReport($rule, false);
    }

    public function simulate(FireblocksSweepRule $rule): array
    {
        return $this->buildReport($rule, true);
    }

    public function buildReport(FireblocksSweepRule $rule, bool $simulate): array
    {
        $this->balances->clearCache();

        $config = SweepConfigService::tryGet() ?? [];
        $destinationVaultId = trim((string) $rule->destination_vault_id);
        if ($destinationVaultId === '') {
            $destinationVaultId = trim((string) ($config['default_omnibus_vault_id'] ?? ''));
        }

        $pairs = $this->evaluation->resolveCandidatePairsForRule($rule, $config, true, 'manual');
        $vaultIds = array_unique(array_column($pairs, 'vault_id'));

        $preloadIds = $vaultIds;
        if ($destinationVaultId !== '') {
            $preloadIds[] = $destinationVaultId;
        }
        $this->balances->preloadVaults(array_values(array_unique($preloadIds)));

        $destinationVaultName = $destinationVaultId !== ''
            ? $this->balances->vaultName($destinationVaultId)
            : 'Not configured';

        $sourceRows = [];
        $destinationInflows = [];
        $assetIdsSeen = [];

        foreach ($pairs as $pair) {
            $vaultId = $pair['vault_id'];
            $assetId = $pair['asset_id'];
            $assetIdsSeen[$assetId] = true;

            $snapshot = $this->balances->getVaultAssetBalance($vaultId, $assetId, false);

            if (!$this->balances->hasPositiveBalance($snapshot['available'], $snapshot['total'])) {
                continue;
            }

            $row = [
                'source_vault_id' => $vaultId,
                'source_vault_name' => $this->balances->vaultName($vaultId),
                'source_vault_title' => $this->balances->vaultName($vaultId),
                'asset_id' => $assetId,
                'available_before' => $snapshot['available'],
                'total_before' => $snapshot['total'],
                'balance_found' => $snapshot['found'],
                'balance_error' => $snapshot['error'],
                'available_after' => $snapshot['available'],
                'total_after' => $snapshot['total'],
                'sweep_amount' => '0',
                'would_sweep' => false,
                'skip_reason' => null,
                'simulated' => $simulate,
            ];

            if ($simulate) {
                $plan = $this->execution->planSweep($rule, $vaultId, $assetId, $config, false);
                $row = array_merge($row, [
                    'max_spendable' => $plan['max_spendable'] ?? '0',
                    'threshold' => $plan['threshold'] ?? '0',
                    'reserve' => $plan['reserve'] ?? '0',
                    'sweep_amount' => $plan['sweep_amount'] ?? '0',
                    'would_sweep' => (bool) ($plan['would_sweep'] ?? false),
                    'skip_reason' => $plan['skip_reason'] ?? null,
                    'available_after' => $plan['available_after'] ?? $snapshot['available'],
                    'total_after' => $plan['total_after'] ?? $snapshot['total'],
                ]);

                if ($row['would_sweep'] && $destinationVaultId !== '') {
                    $destinationInflows[$assetId] = SweepMath::add(
                        $destinationInflows[$assetId] ?? '0',
                        $row['sweep_amount'],
                        18
                    );
                }
            }

            $sourceRows[] = $row;
        }

        $destinationBefore = [];
        $destinationAfter = [];
        $destAssetsMap = $destinationVaultId !== ''
            ? $this->balances->getVaultAssetsMap($destinationVaultId)
            : [];

        foreach (array_keys($assetIdsSeen) as $assetId) {
            if (isset($destAssetsMap[$assetId])) {
                $snap = [
                    'available' => $destAssetsMap[$assetId]['available'],
                    'total' => $destAssetsMap[$assetId]['total'],
                    'found' => true,
                    'error' => null,
                ];
            } elseif ($destinationVaultId === '') {
                $snap = ['available' => '0', 'total' => '0', 'found' => false, 'error' => 'Destination vault not configured'];
            } else {
                $snap = ['available' => '0', 'total' => '0', 'found' => false, 'error' => 'Asset not on destination vault'];
            }

            $inflow = $destinationInflows[$assetId] ?? '0';
            $destinationBefore[] = [
                'asset_id' => $assetId,
                'available' => $snap['available'],
                'total' => $snap['total'],
                'found' => $snap['found'],
                'error' => $snap['error'],
            ];
            $destinationAfter[] = [
                'asset_id' => $assetId,
                'available' => $simulate ? SweepMath::add($snap['available'], $inflow, 18) : $snap['available'],
                'total' => $simulate ? SweepMath::add($snap['total'], $inflow, 18) : $snap['total'],
                'inflow' => $inflow,
            ];
        }

        $wouldSweepCount = count(array_filter($sourceRows, fn ($r) => !empty($r['would_sweep'])));

        return [
            'rule' => $rule,
            'simulated' => $simulate,
            'destination_vault_id' => $destinationVaultId,
            'destination_vault_name' => $destinationVaultName,
            'destination_vault_title' => $destinationVaultName,
            'source_rows' => $sourceRows,
            'destination_before' => $destinationBefore,
            'destination_after' => $destinationAfter,
            'summary' => [
                'pairs_evaluated' => count($sourceRows),
                'would_sweep' => $wouldSweepCount,
                'skipped' => count($sourceRows) - $wouldSweepCount,
                'vaults_scanned' => count($vaultIds),
            ],
        ];
    }
}
