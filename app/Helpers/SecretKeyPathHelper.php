<?php

namespace App\Helpers;

class SecretKeyPathHelper
{
    /**
     * Resolve a filesystem path from module config or .env.
     *
     * Supports absolute paths and paths relative to the Laravel project root
     * (important for cron/CLI where the working directory may differ from web).
     */
    public static function resolve(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        if ($path[0] === DIRECTORY_SEPARATOR) {
            return $path;
        }

        return base_path($path);
    }

    public static function isReadable(?string $path): bool
    {
        $resolved = self::resolve($path);

        return $resolved !== '' && is_readable($resolved);
    }

    /**
     * Read key material from an inline PEM/string or from a resolved filesystem path.
     */
    public static function read(?string $path, ?string $inline = null): ?string
    {
        $inline = trim((string) $inline);
        if ($inline !== '') {
            return $inline;
        }

        if (!self::isReadable($path)) {
            return null;
        }

        $contents = file_get_contents(self::resolve($path));

        if ($contents === false) {
            return null;
        }

        $contents = trim($contents);

        return $contents !== '' ? $contents : null;
    }
}
