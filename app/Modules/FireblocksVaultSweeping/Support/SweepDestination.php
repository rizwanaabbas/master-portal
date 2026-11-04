<?php

namespace App\Modules\FireblocksVaultSweeping\Support;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;

class SweepDestination
{
    public static function isPlaceholderVaultId(?string $vaultId): bool
    {
        $vaultId = strtolower(trim((string) $vaultId));

        // Fireblocks vault account ID "0" is valid (e.g. Omnibus Vault).
        return $vaultId === '' || in_array($vaultId, ['null', 'undefined', 'none'], true);
    }

    /**
     * Resolve omnibus / destination vault ID from rule + module config.
     */
    public static function resolveVaultId(FireblocksSweepRule $rule, ?array $config = null): ?string
    {
        $config = $config ?? [];
        $candidates = [
            trim((string) $rule->destination_vault_id),
            trim((string) ($config['default_omnibus_vault_id'] ?? '')),
        ];

        foreach ($candidates as $candidate) {
            if (!self::isPlaceholderVaultId($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public static function isValidForSource(?string $destinationVaultId, string $sourceVaultId): bool
    {
        if (self::isPlaceholderVaultId($destinationVaultId)) {
            return false;
        }

        return trim((string) $destinationVaultId) !== trim($sourceVaultId);
    }
}
