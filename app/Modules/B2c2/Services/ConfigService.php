<?php

namespace App\Modules\B2c2\Services;

use App\Models\Module;
use App\Models\ModuleConfiguration;
use RuntimeException;

class ConfigService
{
    private static ?array $resolved = null;

    public static function get(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $module = Module::where('name', 'B2c2')->first();

        if (!$module || !$module->is_enabled) {
            throw new RuntimeException('B2c2 module is not enabled.');
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$record) {
            throw new RuntimeException('B2c2 module configuration not found.');
        }

        $config = unserialize($record->configurations);

        if (!is_array($config)) {
            throw new RuntimeException('B2c2 module configuration is corrupt.');
        }

        self::$resolved = [
            'base_url'         => self::val($config, 'base_url', 'https://api.b2c2.com'),
            'api_token'        => self::val($config, 'api_token'),
            'timeout'          => (int) self::val($config, 'timeout', 30),
            'connect_timeout'  => 10,
            'max_retries'      => 3,
            'retry_delay'      => 500,
            'debug'            => false,
            'proxy'            => null,
            'headers'          => [],
        ];

        return self::$resolved;
    }

    public static function flush(): void
    {
        self::$resolved = null;
    }

    private static function val(array $config, string $key, mixed $default = ''): mixed
    {
        return $config[$key]['value'] ?? $default;
    }
}
