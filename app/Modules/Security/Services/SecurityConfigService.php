<?php

namespace App\Modules\Security\Services;

use App\Helpers\Common;
use App\Models\Module;
use App\Models\ModuleConfiguration;

/**
 * Toggleable hardening from Settings → Modules → Security.
 * Master switch is modules.is_enabled. Each checkbox is off by default.
 */
class SecurityConfigService
{
    private static $resolved = null;

    public static function isModuleEnabled(): bool
    {
        try {
            return Common::isModuleEnabled('Security');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function all(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $defaults = [
            'restrict_cors' => false,
            'allowed_origins' => '*',
            'hide_exception_details' => false,
            'encrypt_session' => false,
            'ban_svg_uploads' => false,
            'require_bitpace_hmac' => false,
        ];

        try {
            $module = Module::where('name', 'Security')->first();
            if (!$module || !$module->is_enabled) {
                return self::$resolved = $defaults;
            }

            $record = ModuleConfiguration::where('module_id', $module->id)->first();
            if (!$record) {
                return self::$resolved = $defaults;
            }

            $config = @unserialize($record->configurations, ['allowed_classes' => false]);
            if (!is_array($config)) {
                return self::$resolved = $defaults;
            }

            $val = function (string $key, $default) use ($config) {
                return $config[$key]['value'] ?? $default;
            };

            return self::$resolved = [
                'restrict_cors' => self::isTruthy($val('restrict_cors', '0')),
                'allowed_origins' => trim((string) $val('allowed_origins', '*')),
                'hide_exception_details' => self::isTruthy($val('hide_exception_details', '0')),
                'encrypt_session' => self::isTruthy($val('encrypt_session', '0')),
                'ban_svg_uploads' => self::isTruthy($val('ban_svg_uploads', '0')),
                'require_bitpace_hmac' => self::isTruthy($val('require_bitpace_hmac', '0')),
            ];
        } catch (\Throwable $e) {
            return self::$resolved = $defaults;
        }
    }

    public static function restrictCors(): bool
    {
        return self::all()['restrict_cors'];
    }

    public static function allowedOrigins(): array
    {
        $raw = self::all()['allowed_origins'];
        if ($raw === '' || $raw === '*') {
            return ['*'];
        }

        $origins = array_values(array_filter(array_map('trim', explode(',', $raw))));

        return $origins ?: ['*'];
    }

    public static function hideExceptionDetails(): bool
    {
        return self::all()['hide_exception_details'];
    }

    public static function encryptSession(): bool
    {
        return self::all()['encrypt_session'];
    }

    public static function banSvgUploads(): bool
    {
        return self::all()['ban_svg_uploads'];
    }

    public static function requireBitpaceHmac(): bool
    {
        return self::all()['require_bitpace_hmac'];
    }

    public static function genericClientMessage(): string
    {
        return 'Something went wrong. Please try again.';
    }

    /**
     * Replace stack traces / SQL errors when hide_exception_details is on.
     * Leaves short user-facing messages unchanged.
     */
    public static function sanitizePublicText($text): string
    {
        $text = is_string($text) ? $text : '';
        if ($text === '' || !self::hideExceptionDetails()) {
            return $text;
        }

        if (self::looksLikeInternalError($text)) {
            return self::genericClientMessage();
        }

        return $text;
    }

    public static function applyRuntimeConfig(): void
    {
        if (self::restrictCors()) {
            config(['cors.allowed_origins' => self::allowedOrigins()]);
        }

        if (self::encryptSession()) {
            config(['session.encrypt' => true]);
        }
    }

    public static function flush(): void
    {
        self::$resolved = null;
    }

    private static function isTruthy($value): bool
    {
        return in_array($value, [1, '1', true, 'true', 'on', 'yes'], true);
    }

    private static function looksLikeInternalError(string $text): bool
    {
        $haystack = strtolower($text);

        if (strlen($text) > 280) {
            return true;
        }

        foreach ([
            'sqlstate',
            'pdoexception',
            'stack trace',
            '/vendor/',
            '/app/',
            '.php:',
            'undefined variable',
            'undefined index',
            'syntax error',
            'allowed memory size',
        ] as $needle) {
            if (stripos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
