<?php

namespace App\Services;

use App\Helpers\Common;
use App\Models\Module;
use App\Models\ModuleConfiguration;
use Illuminate\Support\Facades\Cache;

class WorldCheckConfigService
{
    private static ?array $resolved = null;

    /**
     * @return array{worldcheck_enabled:bool,max_screens_per_day:int,report_status_max_retries:int,screening_mode:string}
     */
    public static function defaults(): array
    {
        return [
            'worldcheck_enabled' => true,
            'max_screens_per_day' => 50,
            'report_status_max_retries' => 2,
            'screening_mode' => 'INITIAL',
        ];
    }

    /**
     * @return array{worldcheck_enabled:bool,max_screens_per_day:int,report_status_max_retries:int,screening_mode:string}
     */
    public static function get(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $defaults = self::defaults();

        try {
            if (!Common::isDatabaseAvailable()) {
                return self::$resolved = $defaults;
            }

            if (!Common::isModuleEnabled('WorldCheck')) {
                $defaults['worldcheck_enabled'] = false;

                return self::$resolved = $defaults;
            }

            $module = Module::where('name', 'WorldCheck')->first();
            $record = $module
                ? ModuleConfiguration::where('module_id', $module->id)->first()
                : null;

            if (!$record || empty($record->configurations)) {
                return self::$resolved = $defaults;
            }

            $config = @unserialize($record->configurations);
            if (!is_array($config)) {
                return self::$resolved = $defaults;
            }

            $mode = strtoupper((string) self::val($config, 'screening_mode', $defaults['screening_mode']));
            if (!in_array($mode, ['INITIAL', 'ONGOING'], true)) {
                $mode = 'INITIAL';
            }

            return self::$resolved = [
                'worldcheck_enabled' => self::bool($config, 'worldcheck_enabled', $defaults['worldcheck_enabled']),
                'max_screens_per_day' => max(0, (int) self::val($config, 'max_screens_per_day', $defaults['max_screens_per_day'])),
                'report_status_max_retries' => max(0, (int) self::val($config, 'report_status_max_retries', $defaults['report_status_max_retries'])),
                'screening_mode' => $mode,
            ];
        } catch (\Throwable) {
            return self::$resolved = $defaults;
        }
    }

    public static function isEnabled(): bool
    {
        $config = self::get();

        return !empty($config['worldcheck_enabled']);
    }

    public static function screeningMode(): string
    {
        return self::get()['screening_mode'];
    }

    public static function reportStatusMaxRetries(): int
    {
        return (int) self::get()['report_status_max_retries'];
    }

    public static function maxScreensPerDay(): int
    {
        return (int) self::get()['max_screens_per_day'];
    }

    /**
     * Whether a new screeningRequest is allowed under the daily max.
     */
    public static function canScreen(): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        $max = self::maxScreensPerDay();
        if ($max === 0) {
            return true;
        }

        return self::screensUsedToday() < $max;
    }

    public static function screensUsedToday(): int
    {
        return (int) Cache::get(self::screenCountCacheKey(), 0);
    }

    public static function remainingScreensToday(): ?int
    {
        $max = self::maxScreensPerDay();
        if ($max === 0) {
            return null;
        }

        return max(0, $max - self::screensUsedToday());
    }

    public static function assertCanCallApi(string $action = 'World-Check'): void
    {
        if (!self::isEnabled()) {
            throw new \Exception($action . ' is disabled. Enable the WorldCheck module in System Settings.');
        }
    }

    public static function assertCanScreen(): void
    {
        self::assertCanCallApi('World-Check screening');

        if (!self::canScreen()) {
            $max = self::maxScreensPerDay();
            throw new \Exception(
                "Daily World-Check screening limit reached ({$max}). Try again tomorrow or raise Max screening requests per day."
            );
        }
    }

    public static function recordScreen(): void
    {
        $key = self::screenCountCacheKey();
        if (!Cache::has($key)) {
            Cache::put($key, 1, now()->endOfDay());
        } else {
            Cache::increment($key);
        }
    }

    public static function flush(): void
    {
        self::$resolved = null;
    }

    private static function screenCountCacheKey(): string
    {
        return 'worldcheck_screens_' . now()->format('Y-m-d');
    }

    private static function val(array $config, string $key, mixed $default = ''): mixed
    {
        if (!array_key_exists($key, $config)) {
            return $default;
        }
        $entry = $config[$key];
        if (is_array($entry) && array_key_exists('value', $entry)) {
            return $entry['value'];
        }

        return $entry;
    }

    private static function bool(array $config, string $key, bool $default = false): bool
    {
        $value = self::val($config, $key, $default ? '1' : '0');

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }
}
