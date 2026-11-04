<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\Fireblocks\Models\Asset;
use App\Modules\Fireblocks\Models\FireblocksVaultAccount;
use App\Modules\FireblocksVaultSweeping\Support\SweepMath;
use Fireblocks\Sdk\Facades\Fireblocks;
use Illuminate\Support\Facades\Log;

class SweepBalanceService
{
    /** @var array<string, array<string, array{available: string, total: string}>> */
    protected array $vaultAssetsCache = [];

    /** @var array<string, string> Fireblocks vault account name by vault ID */
    protected array $vaultNamesCache = [];

    public function clearCache(): void
    {
        $this->vaultAssetsCache = [];
        $this->vaultNamesCache = [];
    }

    public function vaultTitleMap(): array
    {
        return FireblocksVaultAccount::query()
            ->pluck('vault_title', 'vault_id')
            ->map(fn ($title) => (string) $title)
            ->all();
    }

    public function vaultTitle(string $vaultId, ?array $titleMap = null): string
    {
        $titleMap = $titleMap ?? $this->vaultTitleMap();

        return $titleMap[$vaultId] ?? ('Vault ' . $vaultId);
    }

    /**
     * Vault display name from Fireblocks (getAccount name), with local DB title fallback.
     */
    public function vaultName(string $vaultId): string
    {
        $vaultId = (string) $vaultId;
        if ($vaultId === '') {
            return '—';
        }

        $cached = trim($this->vaultNamesCache[$vaultId] ?? '');
        if ($cached !== '') {
            return $cached;
        }

        $this->getVaultAssetsMap($vaultId);

        $cached = trim($this->vaultNamesCache[$vaultId] ?? '');
        if ($cached !== '') {
            return $cached;
        }

        $local = trim($this->vaultTitle($vaultId));

        return $local !== '' && $local !== 'Vault ' . $vaultId ? $local : ('Vault ' . $vaultId);
    }

    /** User-facing label: Fireblocks / local name plus vault id when a custom name exists. */
    public function vaultDisplayLabel(string $vaultId): string
    {
        $vaultId = (string) $vaultId;
        if ($vaultId === '') {
            return '—';
        }

        $name = trim($this->vaultName($vaultId));
        if ($name === '' || $name === '—') {
            return $vaultId;
        }

        if ($name === 'Vault ' . $vaultId) {
            return $name;
        }

        return $name . ' (' . $vaultId . ')';
    }

    /**
     * Load all assets on a vault in a single Fireblocks API call (cached per request).
     *
     * @return array<string, array{available: string, total: string}>
     */
    public function getVaultAssetsMap(string $vaultId, bool $forceRefresh = false): array
    {
        if (!$forceRefresh && isset($this->vaultAssetsCache[$vaultId])) {
            return $this->vaultAssetsCache[$vaultId];
        }

        try {
            $account = Fireblocks::vaults()->getAccount($vaultId);
            $assets = [];

            $name = trim((string) ($account->name ?? ''));
            if ($name !== '') {
                $this->vaultNamesCache[$vaultId] = $name;
            }

            foreach ($account->assets ?? [] as $asset) {
                $row = is_array($asset) ? $asset : (array) $asset;
                $id = (string) ($row['id'] ?? '');
                if ($id === '') {
                    continue;
                }

                $assets[$id] = [
                    'available' => SweepMath::fromApiDecimal($row['available'] ?? '0'),
                    'total' => SweepMath::fromApiDecimal($row['total'] ?? '0'),
                ];
            }

            $this->vaultAssetsCache[$vaultId] = $assets;

            return $assets;
        } catch (\Throwable $e) {
            Log::warning('Sweep vault assets fetch failed', [
                'vault_id' => $vaultId,
                'error' => $e->getMessage(),
            ]);

            $this->vaultAssetsCache[$vaultId] = [];

            return [];
        }
    }

    /**
     * Asset IDs present on vault, optionally filtered by rule list and non-UTXO type.
     *
     * @param  array<int, string>  $ruleAssetFilter  empty = all assets on vault
     * @return array<int, string>
     */
    public function listAssetIdsOnVault(
        string $vaultId,
        array $ruleAssetFilter,
        SweepRuleMatcher $matcher,
        bool $nonZeroBalanceOnly = true
    ): array {
        $vaultAssets = $this->getVaultAssetsMap($vaultId);
        $candidateIds = $ruleAssetFilter !== []
            ? array_values(array_intersect(array_keys($vaultAssets), $ruleAssetFilter))
            : array_keys($vaultAssets);

        $accountBasedFilter = $this->accountBasedAssetIdSet();

        return array_values(array_filter($candidateIds, function (string $assetId) use ($vaultAssets, $matcher, $accountBasedFilter, $nonZeroBalanceOnly) {
            if (isset($accountBasedFilter[$assetId]) && !$accountBasedFilter[$assetId]) {
                return false;
            }

            if (!$matcher->isAccountBasedAsset($assetId)) {
                return false;
            }

            if (!$nonZeroBalanceOnly) {
                return true;
            }

            $available = $vaultAssets[$assetId]['available'] ?? '0';

            return SweepMath::isPositive($available);
        }));
    }

    public function hasPositiveBalance($available, $total): bool
    {
        return SweepMath::isPositive($available);
    }

    /**
     * @return array{available: string, total: string, found: bool, error: ?string}
     */
    public function getVaultAssetBalance(string $vaultId, string $assetId, bool $refresh = false): array
    {
        if (!$refresh) {
            $vaultAssets = $this->getVaultAssetsMap($vaultId);
            if (array_key_exists($assetId, $vaultAssets)) {
                return [
                    'available' => $vaultAssets[$assetId]['available'],
                    'total' => $vaultAssets[$assetId]['total'],
                    'found' => true,
                    'error' => null,
                ];
            }

            return [
                'available' => '0',
                'total' => '0',
                'found' => false,
                'error' => 'Asset not on vault',
            ];
        }

        try {
            $asset = Fireblocks::vaults()->refreshAssetBalance($vaultId, $assetId);

            $snapshot = [
                'available' => SweepMath::fromApiDecimal($asset->available ?? '0'),
                'total' => SweepMath::fromApiDecimal($asset->total ?? '0'),
            ];

            if (!isset($this->vaultAssetsCache[$vaultId])) {
                $this->vaultAssetsCache[$vaultId] = [];
            }
            $this->vaultAssetsCache[$vaultId][$assetId] = $snapshot;

            return [
                'available' => $snapshot['available'],
                'total' => $snapshot['total'],
                'found' => true,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::debug('Sweep balance refresh failed', [
                'vault_id' => $vaultId,
                'asset_id' => $assetId,
                'error' => $e->getMessage(),
            ]);

            return [
                'available' => '0',
                'total' => '0',
                'found' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Preload vault asset maps for many vaults (one API call each).
     *
     * @param  array<int, string>  $vaultIds
     */
    public function preloadVaults(array $vaultIds): void
    {
        foreach ($vaultIds as $vaultId) {
            $this->getVaultAssetsMap((string) $vaultId);
        }
    }

    /**
     * @return array<string, bool> asset_id => is account-based
     */
    protected function accountBasedAssetIdSet(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = [];
        foreach (Asset::query()->whereIn('type', ['account', 'token', 'fiat', 'utxo'])->get(['asset_id', 'type']) as $asset) {
            $cache[(string) $asset->asset_id] = $asset->type !== 'utxo';
        }

        return $cache;
    }
}
