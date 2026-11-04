<?php

namespace App\Modules\Fireblocks\Services;

use App\Modules\Fireblocks\Models\Asset;
use App\Modules\Fireblocks\Models\FireblocksWithdrawalPoolBalance;
use App\Modules\FireblocksVaultSweeping\Services\SweepBalanceService;
use App\Modules\FireblocksVaultSweeping\Support\SweepMath;
use Fireblocks\Sdk\Facades\Fireblocks;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WithdrawalPoolBalanceSyncService
{
    public function __construct(
        protected WalletPoolTaggedVaultScanService $poolVaultScan,
        protected SweepBalanceService $vaultBalances,
    ) {
    }

    /**
     * Enabled portal assets routed to the omnibus Source Vault Account ID (UTXO or account type).
     *
     * @return Collection<int, Asset>
     */
    public function enabledSourceVaultAssets(): Collection
    {
        return Asset::query()
            ->enabled()
            ->whereNotNull('asset_id')
            ->where('asset_id', '!=', '')
            ->orderBy('asset_id')
            ->get()
            ->filter(fn (Asset $asset) => WithdrawalSourceResolver::resolveSourceVaultAccountId($asset) !== null)
            ->values();
    }

    /**
     * Fireblocks asset ids that belong on the source vault (omnibus), not the wallet pool grid.
     *
     * @return Collection<string, Asset>
     */
    public function sourceVaultAssetIdsKeyed(): Collection
    {
        return $this->enabledSourceVaultAssets()->keyBy(
            fn (Asset $asset) => (string) $asset->asset_id
        );
    }

    public function isUtxoWithdrawalAsset(Asset $asset): bool
    {
        return WithdrawalSourceResolver::isUtxoAsset($asset);
    }

    /**
     * Omnibus vault whose balances are fetched. Default 0. Not the withdrawal source vault.
     */
    public function configuredOmnibusVaultId(): string
    {
        return FireblocksConfigService::omnibusVaultId();
    }

    /**
     * Enabled portal assets keyed by Fireblocks asset id, for matching a vault account payload.
     *
     * @return Collection<string, Asset>
     */
    public function enabledPortalAssetsByFireblocksId(): Collection
    {
        return Asset::query()
            ->enabled()
            ->whereNotNull('asset_id')
            ->where('asset_id', '!=', '')
            ->orderBy('asset_id')
            ->get()
            ->keyBy(fn (Asset $asset) => (string) $asset->asset_id);
    }

    /**
     * @return array{success: bool, synced: int, message?: string, errors?: array<int, string>}
     */
    public function sync(): array
    {
        $synced = 0;
        $errors = [];

        $omnibusVaultId = $this->configuredOmnibusVaultId();
        $walletPoolId = FireblocksConfigService::walletPoolId();

        $omnibusAssetsById = $this->enabledPortalAssetsByFireblocksId();
        $sourceVaultAssetsById = $this->sourceVaultAssetIdsKeyed();
        $canSyncOmnibus = $omnibusAssetsById->isNotEmpty();
        $canSyncPool = $walletPoolId !== '' && self::isWalletPoolTagUuid($walletPoolId);

        if (!$canSyncOmnibus && !$canSyncPool) {
            $messages = [];
            if ($omnibusAssetsById->isEmpty()) {
                $messages[] = 'No enabled portal assets to match against the omnibus vault.';
            }
            if ($walletPoolId === '') {
                $messages[] = 'Wallet Pool ID is not configured (wallet pool).';
            } elseif (!self::isWalletPoolTagUuid($walletPoolId)) {
                $messages[] = 'Wallet Pool ID must be a valid UUID.';
            }

            return [
                'success' => false,
                'synced' => 0,
                'message' => $messages !== [] ? implode(' ', $messages) : 'Nothing to sync.',
            ];
        }

        if ($canSyncOmnibus) {
            $this->vaultBalances->clearCache();
            $sourceResult = $this->syncSourceVaultBalances($omnibusVaultId, $omnibusAssetsById);
            $synced += $sourceResult['synced'];
            $errors = array_merge($errors, $sourceResult['errors']);
            if ($sourceResult['loaded']) {
                FireblocksWithdrawalPoolBalance::query()
                    ->whereIn('balance_source', ['source_vault', 'utxo_vault'])
                    ->where('vault_account_id', '!=', $omnibusVaultId)
                    ->delete();
            }
        }

        if ($canSyncPool) {
            $this->vaultBalances->clearCache();
            $poolResult = $this->syncWalletPoolMemberBalances($walletPoolId, $sourceVaultAssetsById, $omnibusVaultId);
            $synced += $poolResult['synced'];
            $errors = array_merge($errors, $poolResult['errors']);
        } elseif ($walletPoolId !== '' && !self::isWalletPoolTagUuid($walletPoolId)) {
            $errors[] = 'Fireblocks module Wallet Pool ID must be a valid UUID (current value is invalid).';
        }

        if ($sourceVaultAssetsById->isNotEmpty()) {
            FireblocksWithdrawalPoolBalance::query()
                ->where('balance_source', 'wallet_pool_member')
                ->whereIn('asset_id', $sourceVaultAssetsById->keys()->all())
                ->delete();
        }

        return [
            'success' => $synced > 0,
            'synced' => $synced,
            'message' => $synced > 0
                ? "Synced {$synced} withdrawal pool balance row(s) from Fireblocks."
                : 'No balances could be synced from Fireblocks.',
            'errors' => $errors,
        ];
    }

    /**
     * Omnibus vault — one getAccount, persist intersection with enabled portal assets on that vault.
     *
     * @param  Collection<string, Asset>  $omnibusAssetsById
     * @return array{synced: int, errors: array<int, string>, loaded: bool, asset_ids: array<int, string>}
     */
    protected function syncSourceVaultBalances(string $omnibusVaultId, Collection $omnibusAssetsById): array
    {
        $synced = 0;
        $errors = [];
        $assetIds = [];

        $vaultAssets = $this->vaultBalances->getVaultAssetsMap($omnibusVaultId, true);
        if ($vaultAssets === []) {
            return [
                'synced' => 0,
                'loaded' => false,
                'asset_ids' => [],
                'errors' => [
                    'omnibus-vault:' . $omnibusVaultId . ': could not load vault balances from Fireblocks.',
                ],
            ];
        }

        foreach ($vaultAssets as $fireblocksAssetId => $snapshot) {
            $portalAsset = $omnibusAssetsById->get($fireblocksAssetId);
            if ($portalAsset === null) {
                continue;
            }

            if ($this->persistBalanceSnapshot(
                $portalAsset,
                $omnibusVaultId,
                null,
                'source_vault',
                $snapshot['total'] ?? '0',
                $snapshot['available'] ?? '0',
                ['source' => 'getAccount', 'vault_id' => $omnibusVaultId, 'asset_id' => $fireblocksAssetId]
            )) {
                $synced++;
                $assetIds[] = (string) $fireblocksAssetId;
            }
        }

        if ($synced === 0) {
            $onVault = array_slice(array_keys($vaultAssets), 0, 12);
            $errors[] = 'Omnibus vault ' . $omnibusVaultId . ' has assets [' . implode(', ', $onVault)
                . (count($vaultAssets) > 12 ? ', …' : '')
                . '] but none matched enabled portal assets ('
                . $omnibusAssetsById->keys()->take(12)->implode(', ')
                . ($omnibusAssetsById->count() > 12 ? ', …' : '') . ').';
        }

        return ['synced' => $synced, 'errors' => $errors, 'loaded' => true, 'asset_ids' => $assetIds];
    }

    /**
     * Wallet pool — all assets on each member vault (not filtered by portal asset catalog).
     *
     * @param  Collection<string, Asset>  $sourceVaultAssetsById
     * @return array{synced: int, errors: array<int, string>}
     */
    protected function syncWalletPoolMemberBalances(string $walletPoolId, Collection $sourceVaultAssetsById, string $omnibusVaultId = ''): array
    {
        $synced = 0;
        $errors = [];
        $membersSeen = 0;
        foreach ($this->poolVaultScan->eachPoolMemberPage($walletPoolId) as $page) {
            foreach ($page['accounts'] ?? [] as $account) {
                $account = is_array($account) ? $account : (array) $account;
                $vaultId = trim((string) ($account['id'] ?? ''));
                if ($vaultId === '' || $vaultId === $omnibusVaultId) {
                    continue;
                }

                $membersSeen++;
                $vaultAssets = $this->vaultBalances->getVaultAssetsMap($vaultId, true);
                if ($vaultAssets === []) {
                    $errors[] = 'pool:' . $walletPoolId . ' vault:' . $vaultId . ': could not load vault balances.';
                    continue;
                }

                foreach ($vaultAssets as $fireblocksAssetId => $snapshot) {
                    if ($sourceVaultAssetsById->has($fireblocksAssetId)) {
                        continue;
                    }

                    if ($this->persistRawBalanceSnapshot(
                        (string) $fireblocksAssetId,
                        $vaultId,
                        $walletPoolId,
                        'wallet_pool_member',
                        $snapshot['total'] ?? '0',
                        $snapshot['available'] ?? '0',
                        ['source' => 'getAccount', 'vault_id' => $vaultId, 'asset_id' => $fireblocksAssetId]
                    )) {
                        $synced++;
                    }
                }
            }
        }

        if ($membersSeen === 0) {
            $errors[] = 'No wallet pool member vaults found for pool ' . $walletPoolId . '.';
        } elseif ($synced === 0 && $membersSeen > 0) {
            $errors[] = 'Wallet pool member vaults were found but no asset balances were returned from Fireblocks.';
        }

        return ['synced' => $synced, 'errors' => $errors];
    }

    protected function syncVaultAssetBalance(
        Asset $portalAsset,
        string $vaultId,
        ?string $walletPoolId,
        string $balanceSource
    ): bool {
        $fireblocksAssetId = (string) $portalAsset->asset_id;

        try {
            $refreshed = Fireblocks::vaults()->refreshAssetBalance($vaultId, $fireblocksAssetId);
            $row = is_array($refreshed) ? $refreshed : (array) $refreshed;
            $total = SweepMath::fromApiDecimal($row['total'] ?? $row['balance'] ?? '0');
            $available = SweepMath::fromApiDecimal($row['available'] ?? $total);

            return $this->persistBalanceSnapshot(
                $portalAsset,
                $vaultId,
                $walletPoolId,
                $balanceSource,
                $total,
                $available,
                $row
            );
        } catch (\Throwable $e) {
            Log::warning('Withdrawal pool balance sync failed', [
                'vault_account_id' => $vaultId,
                'wallet_pool_id' => $walletPoolId,
                'asset_id' => $fireblocksAssetId,
                'balance_source' => $balanceSource,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    protected function persistBalanceSnapshot(
        Asset $portalAsset,
        string $vaultId,
        ?string $walletPoolId,
        string $balanceSource,
        string $total,
        string $available,
        array $rawResponse
    ): bool {
        return $this->persistRawBalanceSnapshot(
            (string) $portalAsset->asset_id,
            $vaultId,
            $walletPoolId,
            $balanceSource,
            $total,
            $available,
            $rawResponse,
            $portalAsset->id,
            $portalAsset->currency_id
        );
    }

    /**
     * @param  array<string, mixed>  $rawResponse
     */
    protected function persistRawBalanceSnapshot(
        string $fireblocksAssetId,
        string $vaultId,
        ?string $walletPoolId,
        string $balanceSource,
        string $total,
        string $available,
        array $rawResponse,
        ?int $portalAssetId = null,
        ?int $currencyId = null
    ): bool {
        if ($portalAssetId === null) {
            $catalogAsset = Asset::query()
                ->where('asset_id', $fireblocksAssetId)
                ->orderByDesc('is_enabled')
                ->first();
            if ($catalogAsset) {
                $portalAssetId = $catalogAsset->id;
                $currencyId = $currencyId ?? $catalogAsset->currency_id;
            }
        }

        try {
            $pending = bcsub($total, $available, 18);
            if (bccomp($pending, '0', 18) < 0) {
                $pending = '0';
            }

            FireblocksWithdrawalPoolBalance::updateOrCreate(
                [
                    'vault_account_id' => $vaultId,
                    'asset_id' => $fireblocksAssetId,
                ],
                [
                    'wallet_pool_id' => $walletPoolId,
                    'balance_source' => $balanceSource,
                    'portal_asset_id' => $portalAssetId,
                    'currency_id' => $currencyId,
                    'total' => $total,
                    'available' => $available,
                    'pending' => $pending,
                    'raw_response' => $rawResponse,
                ]
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('Withdrawal pool balance persist failed', [
                'vault_account_id' => $vaultId,
                'asset_id' => $fireblocksAssetId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function isWalletPoolTagUuid(string $walletPoolId): bool
    {
        return Str::isUuid(trim($walletPoolId));
    }

}
