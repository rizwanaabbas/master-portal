<?php

namespace App\Modules\LiveTransactions\Services;

use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Helpers\Common;

class LiveTransactionsConfigService
{
    private static ?array $resolved = null;

    public static function defaults(): array
    {
        return [
            'tray_enabled' => true,
            'max_records_per_page' => 20,
            'asset_filter' => 'both',
            'direction_filter' => 'both',
            'poll_interval_open_seconds' => 3,
            'poll_interval_closed_seconds' => 8,
        ];
    }

    public static function get(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        if (!Common::isModuleEnabled('LiveTransactions')) {
            throw new \RuntimeException('LiveTransactions module is not enabled.');
        }

        $module = Module::where('name', 'LiveTransactions')->first();
        $record = $module ? ModuleConfiguration::where('module_id', $module->id)->first() : null;

        if (!$record) {
            self::$resolved = self::defaults();

            return self::$resolved;
        }

        $config = unserialize($record->configurations);
        if (!is_array($config)) {
            self::$resolved = self::defaults();

            return self::$resolved;
        }

        self::$resolved = [
            'tray_enabled' => self::bool($config, 'tray_enabled', true),
            'max_records_per_page' => max(5, min(100, (int) self::val($config, 'max_records_per_page', 20))),
            'asset_filter' => self::enum($config, 'asset_filter', ['both', 'crypto_only', 'fiat_only'], 'both'),
            'direction_filter' => self::enum($config, 'direction_filter', ['both', 'incoming_only', 'outgoing_only'], 'both'),
            'poll_interval_open_seconds' => max(2, (int) self::val($config, 'poll_interval_open_seconds', 3)),
            'poll_interval_closed_seconds' => max(3, (int) self::val($config, 'poll_interval_closed_seconds', 8)),
        ];

        return self::$resolved;
    }

    public static function tryGet(): ?array
    {
        try {
            if (!Common::isDatabaseAvailable()) {
                return null;
            }

            if (!Common::isModuleEnabled('LiveTransactions')) {
                return null;
            }

            return self::get();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function isTrayActive(): bool
    {
        $config = self::tryGet();

        return $config !== null && !empty($config['tray_enabled']);
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

    private static function enum(array $config, string $key, array $allowed, string $default): string
    {
        $value = (string) self::val($config, $key, $default);

        return in_array($value, $allowed, true) ? $value : $default;
    }
}
