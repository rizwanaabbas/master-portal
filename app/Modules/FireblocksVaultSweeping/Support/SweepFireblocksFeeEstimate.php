<?php

namespace App\Modules\FireblocksVaultSweeping\Support;

/**
 * Helpers for Fireblocks POST /v1/transactions/estimate_fee responses.
 */
class SweepFireblocksFeeEstimate
{
    /**
     * @param  array<string, mixed>  $estimate
     */
    public static function extractNetworkFee(array $estimate, string $feeLevel = 'LOW'): string
    {
        $preferred = strtolower(trim($feeLevel));
        if ($preferred === '') {
            $preferred = 'low';
        }

        $preferredFee = $estimate[$preferred]['networkFee'] ?? $estimate[$preferred]['network_fee'] ?? null;
        if ($preferredFee !== null && is_numeric($preferredFee) && (float) $preferredFee > 0) {
            return (string) $preferredFee;
        }

        foreach (['low', 'medium', 'high'] as $level) {
            $fee = $estimate[$level]['networkFee'] ?? $estimate[$level]['network_fee'] ?? null;
            if ($fee !== null && is_numeric($fee) && (float) $fee > 0) {
                return (string) $fee;
            }
        }

        $direct = $estimate['networkFee'] ?? $estimate['network_fee'] ?? null;
        if ($direct !== null && is_numeric($direct)) {
            return (string) $direct;
        }

        return '0';
    }
}
