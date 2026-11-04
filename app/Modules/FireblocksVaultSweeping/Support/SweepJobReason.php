<?php

namespace App\Modules\FireblocksVaultSweeping\Support;

class SweepJobReason
{
    public static function normalize(?string $reason, int $maxLength = 1000): ?string
    {
        $reason = trim((string) $reason);
        if ($reason === '') {
            return null;
        }

        if (strlen($reason) <= $maxLength) {
            return $reason;
        }

        if ($maxLength <= 1) {
            return substr($reason, 0, $maxLength);
        }

        return substr($reason, 0, $maxLength - 3) . '...';
    }

    public static function fromThrowable(\Throwable $e, int $maxLength = 1000): string
    {
        $message = trim($e->getMessage());
        if ($message === '') {
            $message = $e::class;
        }

        return self::normalize($message, $maxLength) ?? $e::class;
    }
}
