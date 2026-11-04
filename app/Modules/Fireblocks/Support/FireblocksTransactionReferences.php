<?php

namespace App\Modules\Fireblocks\Support;

/**
 * External ids / idempotency keys sent to Fireblocks (no institution branding in new values).
 */
final class FireblocksTransactionReferences
{
    public const SWEEP_EXTERNAL_PREFIX = 'fb-sweep-';

    public const SWEEP_GAS_EXTERNAL_PREFIX = 'fb-sweep-gas-';

    public const MONITOR_IDEMPOTENCY_PREFIX = 'fb-monitor-';

    /** @var string Legacy sweep external tx ids (in-flight / historical webhooks) */
    private const LEGACY_SWEEP_PREFIX = 'cib-sweep-';

    /** @var string Legacy gas-feed external tx ids */
    private const LEGACY_SWEEP_GAS_PREFIX = 'cib-sweep-gas-';

    public static function isVaultSweepExternalTxId(string $externalTxId): bool
    {
        $externalTxId = trim($externalTxId);
        if ($externalTxId === '' || self::isVaultSweepGasExternalTxId($externalTxId)) {
            return false;
        }

        return str_starts_with($externalTxId, self::SWEEP_EXTERNAL_PREFIX)
            || str_starts_with($externalTxId, self::LEGACY_SWEEP_PREFIX);
    }

    public static function isVaultSweepGasExternalTxId(string $externalTxId): bool
    {
        $externalTxId = trim($externalTxId);

        return str_starts_with($externalTxId, self::SWEEP_GAS_EXTERNAL_PREFIX)
            || str_starts_with($externalTxId, self::LEGACY_SWEEP_GAS_PREFIX);
    }

    public static function monitorBoostIdempotencyKey(string $fireblocksTxId, int $attempt): string
    {
        return self::MONITOR_IDEMPOTENCY_PREFIX . $fireblocksTxId . '-boost-' . $attempt;
    }

    public static function buildSweepExternalTxId(int $ruleId, string $sourceVaultId, string $assetId, string $uniquePart): string
    {
        return sprintf(
            '%s%d-%s-%s-%s',
            self::SWEEP_EXTERNAL_PREFIX,
            $ruleId,
            $sourceVaultId,
            $assetId,
            $uniquePart
        );
    }

    public static function buildSweepGasExternalTxId(int $jobId): string
    {
        return substr(
            sprintf('%s%d-%s-%s', self::SWEEP_GAS_EXTERNAL_PREFIX, $jobId, now()->format('YmdHisu'), uniqid('', true)),
            0,
            255
        );
    }
}
