<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\FireblocksVaultSweeping\Support\SweepAssetMetadata;
use App\Modules\FireblocksVaultSweeping\Support\SweepDestination;
use App\Modules\FireblocksVaultSweeping\Support\SweepFireblocksDiagnostics;
use App\Modules\FireblocksVaultSweeping\Support\SweepFireblocksTransactions;
use Fireblocks\Sdk\Models\TransactionRequest;

class SweepGasRelayStatusService
{
    public function __construct(
        protected ?SweepFireblocksTransactions $fireblocksTx = null,
        protected ?TaggedVaultScanService $taggedVaults = null,
    ) {
        $this->fireblocksTx = $fireblocksTx ?? app(SweepFireblocksTransactions::class);
        $this->taggedVaults = $taggedVaults ?? app(TaggedVaultScanService::class);
    }

    /**
     * Probe Fireblocks Universal Gasless / gas relay for an ERC-20 stablecoin asset.
     *
     * Uses POST /v1/transactions/estimate_fee with useGasless=true — there is no separate
     * “relay enabled” read API; a successful estimate indicates the workspace accepts relay.
     *
     * @return array<string, mixed>
     */
    public function probe(
        string $tokenAssetId,
        ?string $destinationVaultId = null,
        ?string $sourceVaultId = null
    ): array {
        $tokenAssetId = trim($tokenAssetId);
        $meta = SweepAssetMetadata::describe(null, $tokenAssetId);

        if (!($meta['supports_gas_relay'] ?? false)) {
            return [
                'asset_id' => $tokenAssetId,
                'supports_gas_relay' => false,
                'status' => 'not_applicable',
                'enabled' => null,
                'message' => 'Gas relay applies to Ethereum ERC-20 USDC/USDT only (not Tron or Base).',
                'gas_asset_id' => $meta['gas_asset_id'] ?? null,
            ];
        }

        $config = SweepConfigService::tryGet() ?? [];
        $destinationVaultId = trim((string) ($destinationVaultId ?? ($config['default_omnibus_vault_id'] ?? '')));
        $gasAssetId = (string) ($meta['gas_asset_id'] ?? 'ETH_TEST5');

        if (SweepDestination::isPlaceholderVaultId($destinationVaultId)) {
            return [
                'asset_id' => $tokenAssetId,
                'supports_gas_relay' => true,
                'status' => 'unknown',
                'enabled' => null,
                'message' => 'Configure Omnibus Vault ID in module settings to probe gas relay via Fireblocks.',
                'gas_asset_id' => $gasAssetId,
            ];
        }

        $sourceVaultId = trim((string) ($sourceVaultId ?? ''));
        if ($sourceVaultId === '') {
            $sourceVaultId = $this->resolveProbeSourceVault($tokenAssetId, $config) ?? '';
        }

        if ($sourceVaultId === '') {
            return [
                'asset_id' => $tokenAssetId,
                'supports_gas_relay' => true,
                'status' => 'unknown',
                'enabled' => null,
                'message' => 'No tagged client deposit vault found holding this asset — cannot probe relay yet.',
                'gas_asset_id' => $gasAssetId,
                'hint' => 'Ensure client_intermediary_tag_id is set and at least one CI vault holds the token.',
            ];
        }

        $probeAmount = '0.000001';
        $txRequest = new TransactionRequest();
        $txRequest
            ->withAsset($tokenAssetId)
            ->fromVaultAccount($sourceVaultId)
            ->toVaultAccount($destinationVaultId)
            ->withAmount($probeAmount)
            ->withFeeLevel('LOW');

        $endpoint = '/v1/transactions/estimate_fee';
        $payload = SweepFireblocksDiagnostics::buildTransactionPayload($txRequest, true);

        try {
            $estimate = $this->fireblocksTx->estimateFee($txRequest, true);

            return [
                'asset_id' => $tokenAssetId,
                'supports_gas_relay' => true,
                'status' => 'enabled',
                'enabled' => true,
                'message' => 'Fireblocks accepted a gas-relay fee estimate (useGasless). Relay appears enabled for this workspace.',
                'gas_asset_id' => $gasAssetId,
                'probe' => [
                    'endpoint' => $endpoint,
                    'method' => 'POST',
                    'source_vault_id' => $sourceVaultId,
                    'destination_vault_id' => $destinationVaultId,
                    'payload' => $payload,
                    'fee_tiers' => array_intersect_key($estimate, array_flip(['low', 'medium', 'high'])),
                ],
            ];
        } catch (\Throwable $e) {
            $message = SweepFireblocksDiagnostics::formatExceptionMessage($e, 'Gas relay probe failed');
            $lower = strtolower($message);
            $looksDisabled = str_contains($lower, 'gasless')
                || str_contains($lower, 'gas relay')
                || str_contains($lower, 'relay')
                || str_contains($lower, 'not enabled')
                || str_contains($lower, 'not supported')
                || str_contains($lower, 'not configured');

            return [
                'asset_id' => $tokenAssetId,
                'supports_gas_relay' => true,
                'status' => $looksDisabled ? 'disabled' : 'error',
                'enabled' => false,
                'message' => $message,
                'gas_asset_id' => $gasAssetId,
                'fireblocks_response' => SweepFireblocksDiagnostics::fireblocksResponseMessage($e),
                'probe' => [
                    'endpoint' => $endpoint,
                    'method' => 'POST',
                    'source_vault_id' => $sourceVaultId,
                    'destination_vault_id' => $destinationVaultId,
                    'payload' => $payload,
                ],
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function resolveProbeSourceVault(string $tokenAssetId, array $config): ?string
    {
        foreach ($this->taggedVaults->eachTaggedVaultPage($config, $tokenAssetId) as $page) {
            foreach ($page['accounts'] ?? [] as $account) {
                $vaultId = (string) ($account['id'] ?? '');
                if ($vaultId !== '') {
                    return $vaultId;
                }
            }
        }

        return null;
    }
}
