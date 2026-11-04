<?php

namespace App\Services;

use App\Helpers\Common;
use App\Models\Module;
use App\Models\ModuleConfiguration;

/**
 * Crypto module configuration (Settings → Module Configurations → Crypto).
 */
class CryptoConfigService
{
    private static ?array $resolved = null;

    public static function flush(): void
    {
        self::$resolved = null;
    }

    public static function isBitpaceModuleEnabled(): bool
    {
        return Common::isModuleEnabled('Bitpace');
    }

    /**
     * Manual crypto (non-Bitpace API flows: admin/client deposit, buy, sell, withdraw, Bitpace Manual).
     * When Bitpace module is enabled, this is always true (config checkboxes are overridden).
     */
    public static function isManualCryptoEnabled(): bool
    {
        if (self::isBitpaceModuleEnabled()) {
            return true;
        }

        return self::boolValue('enable_manual_crypto', true);
    }

    /**
     * Advanced crypto (Fireblocks, liquidity, Nexus, Independent Reserve, etc.).
     * When Bitpace module is enabled, this is always true (config checkboxes are overridden).
     */
    public static function isAdvancedCryptoEnabled(): bool
    {
        if (self::isBitpaceModuleEnabled()) {
            return true;
        }

        return self::boolValue('enable_advanced_crypto', true);
    }

    /**
     * Force stored checkbox values when Bitpace overrides (both on).
     *
     * @param  array<string, array<string, mixed>>  $configArray
     * @return array<string, array<string, mixed>>
     */
    public static function applyBitpaceOverrideToConfigArray(array $configArray): array
    {
        if (!self::isBitpaceModuleEnabled()) {
            return $configArray;
        }

        foreach (['enable_manual_crypto', 'enable_advanced_crypto'] as $key) {
            if (isset($configArray[$key])) {
                $configArray[$key]['value'] = '1';
            }
        }

        return $configArray;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $defaults = [
            'enable_manual_crypto' => true,
            'enable_advanced_crypto' => true,
        ];

        $module = Module::where('name', 'Crypto')->first();
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

        return self::$resolved = [
            'enable_manual_crypto' => self::isManualCryptoEnabled(),
            'enable_advanced_crypto' => self::isAdvancedCryptoEnabled(),
            'enable_manual_crypto_stored' => self::rawBool($config, 'enable_manual_crypto', true),
            'enable_advanced_crypto_stored' => self::rawBool($config, 'enable_advanced_crypto', true),
        ];
    }

    private static function boolValue(string $key, bool $default): bool
    {
        $module = Module::where('name', 'Crypto')->first();
        if (!$module) {
            return $default;
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$record) {
            return $default;
        }

        $config = @unserialize($record->configurations);
        if (!is_array($config)) {
            return $default;
        }

        return self::rawBool($config, $key, $default);
    }

    /**
     * @param  array<string, array<string, mixed>>  $config
     */
    private static function rawBool(array $config, string $key, bool $default): bool
    {
        if (!isset($config[$key]['value'])) {
            return $default;
        }

        $value = $config[$key]['value'];

        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    }
}
