<?php

namespace App\Modules\Aquanow\Services;

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

        $module = Module::where('name', 'Aquanow')->first();

        if (!$module || !$module->is_enabled) {
            throw new RuntimeException('Aquanow module is not enabled.');
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$record) {
            throw new RuntimeException('Aquanow module configuration not found.');
        }

        $config = unserialize($record->configurations);

        if (!is_array($config)) {
            throw new RuntimeException('Aquanow module configuration is corrupt.');
        }

        self::$resolved = [
            'base_url'         => self::val($config, 'base_url', 'https://api.aquanow.io'),
            'market_data_url'  => self::val($config, 'market_data_url', 'https://api.aquanow.io'),
            'api_key'          => self::val($config, 'api_key'),
            'api_secret'       => self::val($config, 'api_secret'),
            'credit_account_id' => self::val($config, 'credit_account_id'),
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
