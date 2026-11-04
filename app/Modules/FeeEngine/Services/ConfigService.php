<?php

namespace App\Modules\FeeEngine\Services;

use App\Helpers\Common;
use App\Models\Module;
use App\Models\ModuleConfiguration;

/**
 * Reads the Fee Engine module configuration (DB-backed, same pattern as B2c2).
 *
 * The module's on/off master switch is Module.is_enabled (toggled from
 * Settings > Modules). The richer settings live in the serialized
 * ModuleConfiguration row.
 */
class ConfigService
{
    private static ?array $resolved = null;

    /**
     * Master switch — is the fee engine turned on system-wide?
     */
    public static function isEnabled(): bool
    {
        return Common::isModuleEnabled('FeeEngine');
    }

    public static function all(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $defaults = [
            'crypto_fee_display'          => 'summed',
            'default_fee_tier_group_id'   => null,
            'applicable_currency_types'   => ['crypto'],
            'rounding_mode'               => 'half_up',
            'rounding_precision_crypto'   => 8,
            'rounding_precision_fiat'     => 2,
            'block_on_missing_tier'       => false,
            'integrate_fee_helper_crypto' => false,
            'integrate_fee_helper_fiat'   => false,
        ];

        $module = Module::where('name', 'FeeEngine')->first();
        if (!$module) {
            return self::$resolved = $defaults;
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$record) {
            return self::$resolved = $defaults;
        }

        $config = @unserialize($record->configurations);
        if (!is_array($config)) {
            return self::$resolved = $defaults;
        }

        $val = fn (string $key, $default) => $config[$key]['value'] ?? $default;

        // Applicable currency types are now driven by two checkboxes.
        $types = [];
        if ((bool) $val('applicable_crypto', '1')) {
            $types[] = 'crypto';
        }
        if ((bool) $val('applicable_fiat', '0')) {
            $types[] = 'fiat';
        }

        return self::$resolved = [
            'crypto_fee_display'          => in_array($val('crypto_fee_display', 'summed'), ['separate', 'summed'], true) ? $val('crypto_fee_display', 'summed') : 'summed',
            'default_fee_tier_group_id'   => $val('default_fee_tier_group_id', null) ?: null,
            'applicable_currency_types'   => $types ?: ['crypto'],
            'rounding_mode'               => $val('rounding_mode', 'half_up'),
            'rounding_precision_crypto'   => (int) $val('rounding_precision_crypto', 8),
            'rounding_precision_fiat'     => (int) $val('rounding_precision_fiat', 2),
            'block_on_missing_tier'       => (bool) $val('block_on_missing_tier', false),
            'integrate_fee_helper_crypto' => (bool) $val('integrate_fee_helper_crypto', false),
            'integrate_fee_helper_fiat'   => (bool) $val('integrate_fee_helper_fiat', false),
        ];
    }

    public static function get(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    public static function cryptoFeeDisplay(): string
    {
        return self::get('crypto_fee_display', 'summed');
    }

    public static function appliesTo(string $currencyType): bool
    {
        return in_array($currencyType, self::get('applicable_currency_types', ['crypto']), true);
    }

    /**
     * Whether to also pull fees from the existing FeeHelper for this currency type.
     */
    public static function feeHelperEnabledFor(string $currencyType): bool
    {
        return $currencyType === 'fiat'
            ? (bool) self::get('integrate_fee_helper_fiat', false)
            : (bool) self::get('integrate_fee_helper_crypto', false);
    }

    public static function flush(): void
    {
        self::$resolved = null;
    }
}
