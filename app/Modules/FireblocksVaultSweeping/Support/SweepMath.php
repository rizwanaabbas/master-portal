<?php

namespace App\Modules\FireblocksVaultSweeping\Support;

/**
 * Decimal math for sweep amounts. Uses ext-bcmath when available; float fallback otherwise.
 */
class SweepMath
{
    public static function normalize($value): string
    {
        $value = trim((string) $value);

        if ($value === '' || !is_numeric($value)) {
            return '0';
        }

        return $value;
    }

    /**
     * Normalize Fireblocks API balances (JSON numbers decode as float in PHP).
     */
    public static function fromApiDecimal($value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return self::decimalFromFloat($value);
        }

        $value = trim((string) $value);
        if ($value === '' || !is_numeric($value)) {
            return '0';
        }

        if (stripos($value, 'e') !== false && function_exists('bcadd')) {
            return bcadd($value, '0', 18);
        }

        return self::sanitizeTransactionAmount($value);
    }

    /**
     * Final amount string for Fireblocks API (trim float noise, strip trailing zeros).
     */
    public static function sanitizeTransactionAmount(string $amount, int $maxFractionDigits = 12): string
    {
        $amount = self::normalize($amount);
        if ($amount === '0' || !function_exists('bcadd')) {
            return $amount === '0' ? '0' : self::stripTrailingZeros(self::format((float) $amount, $maxFractionDigits));
        }

        if (stripos($amount, 'e') !== false) {
            $amount = bcadd($amount, '0', $maxFractionDigits);
        }

        $amount = self::trimExcessDecimalDigits($amount, $maxFractionDigits);

        return self::stripTrailingZeros($amount);
    }

    public static function stripTrailingZeros(string $value): string
    {
        if (!str_contains($value, '.')) {
            return $value;
        }

        $value = rtrim(rtrim($value, '0'), '.');

        return $value === '' ? '0' : $value;
    }

    /**
     * Trim IEEE-754 tails (e.g. 0.027000000000000007) from API / (string) float casts.
     */
    public static function trimExcessDecimalDigits(string $value, int $maxFractionDigits = 12): string
    {
        $value = self::normalize($value);
        if (!str_contains($value, '.') || !function_exists('bcadd')) {
            return $value;
        }

        $fraction = substr(strstr($value, '.'), 1);
        if (strlen($fraction) <= $maxFractionDigits) {
            return $value;
        }

        return bcadd($value, '0', $maxFractionDigits);
    }

    /**
     * Amount safe to send to Fireblocks: never above available; trim float tails.
     */
    public static function forTransactionAmount(string $amount, string $available, int $scale = 18): string
    {
        $amount = self::sanitizeTransactionAmount($amount);
        $available = self::sanitizeTransactionAmount($available);

        if (!function_exists('bccomp')) {
            $a = (float) $amount;
            $v = (float) $available;

            return self::sanitizeTransactionAmount(self::format($a <= $v ? $a : $v, $scale));
        }

        $amount = bcadd($amount, '0', $scale);
        $available = bcadd($available, '0', $scale);

        if (bccomp($available, '0', $scale) <= 0) {
            return self::sanitizeTransactionAmount($amount);
        }

        if (bccomp($amount, $available, $scale) > 0) {
            return self::sanitizeTransactionAmount($available);
        }

        $gap = bcsub($available, $amount, $scale);
        $dust = bcdiv('1', bcpow('10', (string) min(10, $scale), 0), $scale);
        if (bccomp($gap, '0', $scale) >= 0 && bccomp($gap, $dust, $scale) <= 0) {
            return self::sanitizeTransactionAmount($available);
        }

        return self::sanitizeTransactionAmount($amount);
    }

    protected static function decimalFromFloat(float $value): string
    {
        if (!is_finite($value)) {
            return '0';
        }

        if ($value === 0.0) {
            return '0';
        }

        if (function_exists('bcadd')) {
            $negative = $value < 0;
            $value = abs($value);
            $string = rtrim(rtrim(sprintf('%.14F', $value), '0'), '.');
            if ($string === '') {
                return '0';
            }

            $decimal = bcadd($string, '0', 18);
            $decimal = $negative ? ('-' . ltrim($decimal, '-')) : $decimal;

            return self::sanitizeTransactionAmount($decimal);
        }

        return self::sanitizeTransactionAmount(self::format($value, 18));
    }

    public static function add($left, $right, int $scale = 18): string
    {
        $left = self::normalize($left);
        $right = self::normalize($right);

        if (function_exists('bcadd')) {
            return bcadd($left, $right, $scale);
        }

        return self::format((float) $left + (float) $right, $scale);
    }

    public static function sub($left, $right, int $scale = 18): string
    {
        $left = self::normalize($left);
        $right = self::normalize($right);

        if (function_exists('bcsub')) {
            return bcsub($left, $right, $scale);
        }

        return self::format((float) $left - (float) $right, $scale);
    }

    public static function mul($left, $right, int $scale = 18): string
    {
        $left = self::normalize($left);
        $right = self::normalize($right);

        if (function_exists('bcmul')) {
            return bcmul($left, $right, $scale);
        }

        return self::format((float) $left * (float) $right, $scale);
    }

    public static function comp($left, $right, int $scale = 18): int
    {
        $left = self::normalize($left);
        $right = self::normalize($right);

        if (function_exists('bccomp')) {
            return bccomp($left, $right, $scale);
        }

        $leftFloat = (float) $left;
        $rightFloat = (float) $right;

        if ($leftFloat < $rightFloat) {
            return -1;
        }

        if ($leftFloat > $rightFloat) {
            return 1;
        }

        return 0;
    }

    public static function isPositive($amount, int $scale = 18): bool
    {
        $amount = trim((string) $amount);

        if ($amount === '' || !is_numeric($amount)) {
            return false;
        }

        if (stripos($amount, 'e') !== false) {
            return (float) $amount > 0;
        }

        return self::comp($amount, '0', $scale) > 0;
    }

    protected static function format(float $value, int $scale): string
    {
        return number_format($value, $scale, '.', '');
    }
}
