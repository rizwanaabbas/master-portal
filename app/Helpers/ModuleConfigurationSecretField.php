<?php

namespace App\Helpers;

class ModuleConfigurationSecretField
{
    public const MASK = 'xxxxxxxxxxxxxxxx';

    private const KEY_PATTERNS = [
        '/(?:^|_)api[_-]?key$/i',
        '/(?:^|_)api[_-]?secret$/i',
        '/(?:^|_)client[_-]?secret$/i',
        '/(?:^|_)secret[_-]?key$/i',
        '/(?:^|_)webhook[_-]?secret$/i',
        '/(?:^|_)api[_-]?password$/i',
        '/(?:^|_)merchant[_-]?password$/i',
        '/(?:^|_)callback[_-]?secret$/i',
        '/(?:^|_)app[_-]?token$/i',
        '/(?:^|_)private[_-]?key$/i',
        '/(?:^|_)passphrase$/i',
        '/(?:^|_)auth[_-]?token$/i',
        '/(?:^|_)access[_-]?token$/i',
        '/(?:^|_)sandbox[_-]?secret[_-]?key$/i',
        '/(?:^|_)production[_-]?secret[_-]?key$/i',
        '/(?:^|_)sandbox[_-]?secret$/i',
        '/(?:^|_)production[_-]?secret$/i',
    ];

    public static function isSensitive(string $key, array $field = []): bool
    {
        if (!empty($field['sensitive'])) {
            return true;
        }

        if (($field['type'] ?? '') === 'password') {
            return true;
        }

        $label = strtolower((string) ($field['label'] ?? ''));
        $normalizedKey = strtolower($key);

        if (preg_match('/_(path|file_path|key_path|base_url|public_key)$/i', $normalizedKey)) {
            return false;
        }

        if (preg_match('/\b(path|base url|public key)\b/i', $label)) {
            return false;
        }

        foreach (self::KEY_PATTERNS as $pattern) {
            if (preg_match($pattern, $normalizedKey) || preg_match($pattern, str_replace(' ', '_', $label))) {
                return true;
            }
        }

        if (preg_match('/\b(api key|api secret|secret key|client secret|private key|app token|webhook secret)\b/i', $label)) {
            return true;
        }

        return self::isApiKeyField($key, $field);
    }

    public static function isApiKeyField(string $key, array $field = []): bool
    {
        $haystack = strtolower($key . ' ' . str_replace(['-', '_'], ' ', (string) ($field['label'] ?? '')));

        return (bool) preg_match('/api[\s_-]*key/', $haystack);
    }

    public static function shouldSkipImportedValue(string $key, array $field = []): bool
    {
        return self::isSensitive($key, $field) || self::isApiKeyField($key, $field);
    }

    public static function stripValuesForExport(array $configurations): array
    {
        foreach ($configurations as $key => &$field) {
            if (!is_array($field)) {
                continue;
            }

            if (!self::shouldSkipImportedValue($key, $field)) {
                continue;
            }

            $field['value'] = '';
            $field['_skipped_secret'] = true;
        }

        unset($field);

        return $configurations;
    }

    public static function replaceSkippingSecrets(array $incoming, array $existing): array
    {
        $merged = $incoming;

        foreach ($merged as $key => $field) {
            if (!is_array($field)) {
                continue;
            }

            unset($merged[$key]['_skipped_secret'], $merged[$key]['_has_stored_secret']);

            if (!self::shouldSkipImportedValue($key, is_array($field) ? $field : [])) {
                continue;
            }

            $merged[$key]['value'] = is_array($existing[$key] ?? null)
                ? ($existing[$key]['value'] ?? '')
                : '';
        }

        foreach ($existing as $key => $field) {
            if (!is_array($field) || isset($merged[$key])) {
                continue;
            }

            if (self::shouldSkipImportedValue($key, $field)) {
                $merged[$key] = $field;
            }
        }

        return $merged;
    }

    public static function hasStoredValue(array $field): bool
    {
        return trim((string) ($field['value'] ?? '')) !== '';
    }

    public static function isMaskedSubmission(?string $value): bool
    {
        $value = trim((string) $value);

        if ($value === '') {
            return true;
        }

        if ($value === self::MASK) {
            return true;
        }

        if (preg_match('/^x{4,}$/i', $value)) {
            return true;
        }

        if (preg_match('/^[•\*\.]{4,}$/u', $value)) {
            return true;
        }

        return false;
    }

    public static function maskForDisplay(array $configurations): array
    {
        foreach ($configurations as $key => &$field) {
            if (!is_array($field)) {
                continue;
            }

            if (!self::isSensitive($key, $field)) {
                continue;
            }

            $field['_has_stored_secret'] = self::hasStoredValue($field);
            unset($field['value']);
        }

        unset($field);

        return $configurations;
    }

    public static function shouldPreserveExisting(string $key, array $field, mixed $submittedValue): bool
    {
        if (!self::isSensitive($key, $field)) {
            return false;
        }

        if (!self::hasStoredValue($field)) {
            return false;
        }

        if (is_array($submittedValue)) {
            $submittedValue = implode(',', $submittedValue);
        }

        return self::isMaskedSubmission((string) $submittedValue);
    }
}
