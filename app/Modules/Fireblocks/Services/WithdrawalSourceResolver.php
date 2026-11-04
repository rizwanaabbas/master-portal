<?php

namespace App\Modules\Fireblocks\Services;

use App\Modules\Fireblocks\Models\Asset;

class WithdrawalSourceResolver
{
    public static function isUtxoAsset(Asset $asset): bool
    {
        return strtolower(trim((string) ($asset->type ?? ''))) === 'utxo';
    }

    /**
     * Omnibus / module source vault for this asset, when withdrawals (and balance sync) should use Source Vault Account ID.
     */
    public static function resolveSourceVaultAccountId(Asset $asset): ?string
    {
        $configuredVault = FireblocksConfigService::sourceVaultAccountId();

        if (self::isUtxoAsset($asset)) {
            if ($configuredVault !== '') {
                return $configuredVault;
            }

            $vaultId = trim((string) ($asset->vault_account_id ?? ''));

            return $vaultId !== '' ? $vaultId : null;
        }

        if ($configuredVault === '') {
            return null;
        }

        $assetVaultId = trim((string) ($asset->vault_account_id ?? ''));
        if ($assetVaultId === '') {
            return null;
        }

        return $assetVaultId === $configuredVault ? $configuredVault : null;
    }

    public static function resolveWalletPoolId(Asset $asset): ?string
    {
        if (self::resolveSourceVaultAccountId($asset) !== null) {
            return null;
        }

        $poolId = FireblocksConfigService::walletPoolId();

        return $poolId !== '' ? $poolId : null;
    }

    /**
     * @return array{source_vault_account_id: ?string, wallet_pool_id: ?string}
     */
    public static function resolveForAsset(Asset $asset): array
    {
        $sourceVault = self::resolveSourceVaultAccountId($asset);
        if ($sourceVault !== null) {
            return [
                'source_vault_account_id' => $sourceVault,
                'wallet_pool_id' => null,
            ];
        }

        $poolId = self::resolveWalletPoolId($asset);

        return [
            'source_vault_account_id' => null,
            'wallet_pool_id' => $poolId,
        ];
    }
}
