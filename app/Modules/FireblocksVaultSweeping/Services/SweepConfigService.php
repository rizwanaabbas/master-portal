<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Models\Module;
use App\Models\ModuleConfiguration;
use RuntimeException;

class SweepConfigService
{
    private static ?array $resolved = null;

    public static function get(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $module = Module::where('name', 'FireblocksVaultSweeping')->first();

        if (!$module || !$module->is_enabled) {
            throw new RuntimeException('FireblocksVaultSweeping module is not enabled.');
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$record) {
            throw new RuntimeException('FireblocksVaultSweeping configuration not found.');
        }

        $config = unserialize($record->configurations);

        if (!is_array($config)) {
            throw new RuntimeException('FireblocksVaultSweeping configuration is corrupt.');
        }

        self::$resolved = [
            'sweeping_enabled' => self::bool($config, 'sweeping_enabled'),
            'default_omnibus_vault_id' => trim((string) self::val($config, 'default_omnibus_vault_id', '')),
            'scheduled_interval_minutes' => max(1, (int) self::val($config, 'scheduled_interval_minutes', 5)),
            'max_sweeps_per_run' => max(1, (int) self::val($config, 'max_sweeps_per_run', 50)),
            'dry_run_mode' => self::bool($config, 'dry_run_mode'),
            'default_deposit_threshold' => (string) self::val($config, 'default_deposit_threshold', '0'),
            'default_scheduled_threshold' => (string) self::val($config, 'default_scheduled_threshold', '0'),
            'default_tron_station_vault_id' => trim((string) self::val($config, 'default_tron_station_vault_id', '')),
            'client_intermediary_tag_id' => trim((string) self::val($config, 'client_intermediary_tag_id', '')),
            'gas_wait_stale_minutes' => max(60, (int) self::val($config, 'gas_wait_stale_minutes', 480)),
        ];

        return self::$resolved;
    }

    public static function tryGet(): ?array
    {
        try {
            return self::get();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function isActive(): bool
    {
        $config = self::tryGet();

        return $config !== null && !empty($config['sweeping_enabled']);
    }

    public static function flush(): void
    {
        self::$resolved = null;
    }

    private static function val(array $config, string $key, mixed $default = ''): mixed
    {
        return $config[$key]['value'] ?? $default;
    }

    private static function bool(array $config, string $key, bool $default = false): bool
    {
        $value = self::val($config, $key, $default ? '1' : '0');

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    private static function csv(array $config, string $key): array
    {
        $raw = trim((string) self::val($config, $key, ''));
        if ($raw === '') {
            return [];
        }

        $items = preg_split('/[\s,]+/', $raw) ?: [];

        return array_values(array_filter(array_map('trim', $items)));
    }
}
