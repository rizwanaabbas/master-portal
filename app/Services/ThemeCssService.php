<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Clone stock theme CSS, remap palette colors, and write bank-specific custom themes
 * under public/assets/css/themes/custom/ (gitignored so deploys do not overwrite them).
 */
class ThemeCssService
{
    public const CUSTOM_DIR = 'custom';

    public const COLOR_KEYS = [
        'primary',
        'secondary',
        'success',
        'info',
        'warning',
        'danger',
        'dark',
    ];

    /** Badge variants editable independently of the main theme palette. */
    public const BADGE_KEYS = [
        'primary',
        'secondary',
        'success',
        'info',
        'warning',
        'danger',
        'dark',
    ];

    public function themesPath(): string
    {
        return public_path('assets/css/themes');
    }

    public function customPath(): string
    {
        return $this->themesPath() . DIRECTORY_SEPARATOR . self::CUSTOM_DIR;
    }

    public function ensureCustomDirectory(): void
    {
        $dir = $this->customPath();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create custom themes directory.');
        }

        $gitignore = $dir . DIRECTORY_SEPARATOR . '.gitignore';
        if (!is_file($gitignore)) {
            file_put_contents($gitignore, "*\n!.gitignore\n");
        }
    }

    /**
     * Stock themes only (cust-theme-*.css), excluding maps and custom/.
     *
     * @return string[]
     */
    public function listStockThemes(): array
    {
        $files = glob($this->themesPath() . '/cust-theme-*.css') ?: [];
        $names = array_map('basename', $files);
        sort($names, SORT_NATURAL);

        return $names;
    }

    /**
     * @return string[] basenames relative to themes/ e.g. custom/foo.css
     */
    public function listCustomThemes(): array
    {
        $this->ensureCustomDirectory();
        $files = glob($this->customPath() . '/*.css') ?: [];
        $names = [];
        foreach ($files as $file) {
            $names[] = self::CUSTOM_DIR . '/' . basename($file);
        }
        sort($names, SORT_NATURAL);

        return $names;
    }

    public function resolveThemePath(string $theme): string
    {
        $theme = $this->sanitizeThemeFilename($theme);
        $path = $this->themesPath() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $theme);

        $realThemes = realpath($this->themesPath());
        $realFile = realpath($path);
        if ($realThemes === false || $realFile === false || strpos($realFile, $realThemes) !== 0) {
            throw new InvalidArgumentException('Theme file not found or outside themes directory.');
        }
        if (!is_file($realFile) || strtolower(pathinfo($realFile, PATHINFO_EXTENSION)) !== 'css') {
            throw new InvalidArgumentException('Theme CSS file is invalid.');
        }

        return $realFile;
    }

    public function isCustomTheme(string $theme): bool
    {
        $theme = str_replace('\\', '/', ltrim($theme, '/'));

        return str_starts_with($theme, self::CUSTOM_DIR . '/');
    }

    /**
     * Read :root palette from a theme CSS file.
     *
     * @return array{primary:string,secondary:string,success:string,info:string,warning:string,danger:string,dark:string,shades:array}
     */
    public function extractPalette(string $theme): array
    {
        $css = file_get_contents($this->resolveThemePath($theme));
        if ($css === false) {
            throw new RuntimeException('Unable to read theme CSS.');
        }

        $palette = [];
        foreach (self::COLOR_KEYS as $key) {
            $var = $key === 'dark' ? '--theme-dark' : '--theme-' . $key;
            $palette[$key] = $this->matchCssVar($css, $var) ?? '#000000';
        }

        $shades = [];
        foreach (['primary', 'success', 'info', 'warning', 'danger', 'fusion'] as $family) {
            foreach ([50, 100, 200, 300, 400, 500, 600, 700, 800, 900] as $step) {
                $hex = $this->matchCssVar($css, "--theme-{$family}-{$step}");
                if ($hex) {
                    $shades[$family][$step] = $hex;
                }
            }
        }

        $palette['shades'] = $shades;

        return $palette;
    }

    /**
     * Badge colors from a theme: prefer prior custom overrides, else palette defaults.
     *
     * @return array<string,string>
     */
    public function extractBadgeColors(string $theme, ?array $palette = null): array
    {
        $palette = $palette ?: $this->extractPalette($theme);
        $defaults = [];
        foreach (self::BADGE_KEYS as $key) {
            $defaults[$key] = $palette[$key] ?? '#000000';
        }

        try {
            $css = file_get_contents($this->resolveThemePath($theme));
        } catch (Throwable $e) {
            return $defaults;
        }
        if ($css === false) {
            return $defaults;
        }

        foreach (self::BADGE_KEYS as $key) {
            $fromVar = $this->matchCssVar($css, '--pm-badge-' . $key);
            if ($fromVar) {
                $defaults[$key] = $fromVar;
                continue;
            }
            // Match the first .badge-{key} { ... background-color: #hex }
            $pattern = '/\.badge-' . preg_quote($key, '/') . '\s*\{[^}]*background-color:\s*(#[0-9a-fA-F]{3,8}|[a-zA-Z]+)\s*;/i';
            if (preg_match($pattern, $css, $m)) {
                $defaults[$key] = $this->normalizeHex($m[1]);
            }
        }

        return $defaults;
    }

    /**
     * Text (ink) colors for solid palette surfaces (buttons, alerts, etc.).
     *
     * @return array<string,string>
     */
    public function extractTextColors(string $theme, ?array $palette = null): array
    {
        $palette = $palette ?: $this->extractPalette($theme);
        $defaults = [];
        foreach (self::COLOR_KEYS as $key) {
            $defaults[$key] = $this->contrastInk($palette[$key] ?? '#000000');
        }

        try {
            $css = file_get_contents($this->resolveThemePath($theme));
        } catch (Throwable $e) {
            return $defaults;
        }
        if ($css === false) {
            return $defaults;
        }

        foreach (self::COLOR_KEYS as $key) {
            $fromVar = $this->matchCssVar($css, '--pm-' . $key . '-text');
            if ($fromVar) {
                $defaults[$key] = $fromVar;
            }
        }

        return $defaults;
    }

    /**
     * Text (ink) colors for badge surfaces.
     *
     * @return array<string,string>
     */
    public function extractBadgeTextColors(string $theme, ?array $badgeColors = null): array
    {
        $badgeColors = $badgeColors ?: $this->extractBadgeColors($theme);
        $defaults = [];
        foreach (self::BADGE_KEYS as $key) {
            $defaults[$key] = $this->contrastInk($badgeColors[$key] ?? '#000000');
        }

        try {
            $css = file_get_contents($this->resolveThemePath($theme));
        } catch (Throwable $e) {
            return $defaults;
        }
        if ($css === false) {
            return $defaults;
        }

        foreach (self::BADGE_KEYS as $key) {
            $fromVar = $this->matchCssVar($css, '--pm-badge-' . $key . '-text');
            if ($fromVar) {
                $defaults[$key] = $fromVar;
            }
        }

        return $defaults;
    }

    /**
     * Button hover colors for each palette key (defaults to darkened BG).
     *
     * @return array<string,string>
     */
    public function extractHoverColors(string $theme, ?array $palette = null): array
    {
        $palette = $palette ?: $this->extractPalette($theme);
        $defaults = [];
        foreach (self::COLOR_KEYS as $key) {
            $defaults[$key] = $this->adjustBrightness($palette[$key] ?? '#000000', -0.12);
        }

        try {
            $css = file_get_contents($this->resolveThemePath($theme));
        } catch (Throwable $e) {
            return $defaults;
        }
        if ($css === false) {
            return $defaults;
        }

        foreach (self::COLOR_KEYS as $key) {
            $fromVar = $this->matchCssVar($css, '--pm-' . $key . '-hover');
            if ($fromVar) {
                $defaults[$key] = $fromVar;
            }
        }

        return $defaults;
    }

    /**
     * Build a remapped custom theme from a base stock/custom theme.
     *
     * @param  array<string,string>  $colors  primary/secondary/success/info/warning/danger/dark
     * @param  array<string,string>|null  $badgeColors  optional independent badge-* colors
     * @param  array<string,string>|null  $textColors  ink on palette surfaces
     * @param  array<string,string>|null  $badgeTextColors  ink on badge surfaces
     * @param  array<string,string>|null  $hoverColors  button hover backgrounds
     * @return string relative theme path e.g. custom/bank-acme-20261104143022.css
     */
    public function createCustomTheme(
        string $baseTheme,
        array $colors,
        ?string $desiredName = null,
        ?array $badgeColors = null,
        ?array $textColors = null,
        ?array $badgeTextColors = null,
        ?array $hoverColors = null
    ): string {
        $this->ensureCustomDirectory();

        $basePath = $this->resolveThemePath($baseTheme);
        $css = file_get_contents($basePath);
        if ($css === false) {
            throw new RuntimeException('Unable to read base theme.');
        }

        $oldPalette = $this->extractPalette($baseTheme);
        $newColors = $this->normalizeColorInput($colors);
        $texts = $this->normalizeTextColorInput($textColors, $newColors);
        $hovers = $this->normalizeHoverColorInput($hoverColors, $newColors);
        $badges = $this->normalizeBadgeColorInput($badgeColors ?: $newColors);
        $badgeTexts = $this->normalizeTextColorInput($badgeTextColors, $badges, self::BADGE_KEYS);
        $replacementMap = $this->buildReplacementMap($oldPalette, $newColors);

        $newCss = $this->applyReplacements($css, $replacementMap);
        $newCss = $this->rewriteRootBlock($newCss, $oldPalette, $newColors);
        $newCss = $this->appendThemeOverrides($newCss, $newColors, $texts, $badges, $badgeTexts, $hovers);

        $filename = $this->buildCustomFilename($desiredName);
        $relative = self::CUSTOM_DIR . '/' . $filename;
        $target = $this->customPath() . DIRECTORY_SEPARATOR . $filename;

        if (file_put_contents($target, $newCss) === false) {
            throw new RuntimeException('Unable to write custom theme file.');
        }

        return $relative;
    }

    /**
     * Overwrite an existing custom theme (never stock themes).
     *
     * @param  array<string,string>  $colors
     * @param  array<string,string>|null  $badgeColors
     * @param  array<string,string>|null  $textColors
     * @param  array<string,string>|null  $badgeTextColors
     * @param  array<string,string>|null  $hoverColors
     */
    public function updateCustomTheme(
        string $customTheme,
        string $baseTheme,
        array $colors,
        ?array $badgeColors = null,
        ?array $textColors = null,
        ?array $badgeTextColors = null,
        ?array $hoverColors = null
    ): string {
        if (!$this->isCustomTheme($customTheme)) {
            throw new InvalidArgumentException('Only custom themes can be overwritten. Save as a new name for stock themes.');
        }

        $this->ensureCustomDirectory();

        $basePath = $this->resolveThemePath($baseTheme);
        $css = file_get_contents($basePath);
        if ($css === false) {
            throw new RuntimeException('Unable to read base theme.');
        }

        $oldPalette = $this->extractPalette($baseTheme);
        $newColors = $this->normalizeColorInput($colors);
        $texts = $this->normalizeTextColorInput($textColors, $newColors);
        $hovers = $this->normalizeHoverColorInput($hoverColors, $newColors);
        $badges = $this->normalizeBadgeColorInput($badgeColors ?: $newColors);
        $badgeTexts = $this->normalizeTextColorInput($badgeTextColors, $badges, self::BADGE_KEYS);
        $replacementMap = $this->buildReplacementMap($oldPalette, $newColors);

        $newCss = $this->applyReplacements($css, $replacementMap);
        $newCss = $this->rewriteRootBlock($newCss, $oldPalette, $newColors);
        $newCss = $this->appendThemeOverrides($newCss, $newColors, $texts, $badges, $badgeTexts, $hovers);

        $target = $this->resolveThemePath($customTheme);
        if (file_put_contents($target, $newCss) === false) {
            throw new RuntimeException('Unable to update custom theme file.');
        }

        return str_replace('\\', '/', substr($target, strlen(realpath($this->themesPath()) . DIRECTORY_SEPARATOR)));
    }

    public function sanitizeThemeFilename(string $theme): string
    {
        $theme = str_replace('\\', '/', trim($theme));
        $theme = ltrim($theme, '/');
        if ($theme === '' || str_contains($theme, '..')) {
            throw new InvalidArgumentException('Invalid theme filename.');
        }

        return $theme;
    }

    public function buildCustomFilename(?string $desiredName = null): string
    {
        $timestamp = date('YmdHis');

        if ($desiredName === null || trim($desiredName) === '') {
            return 'bank-' . $timestamp . '.css';
        }

        $name = strtolower(trim($desiredName));
        $name = preg_replace('/\.css$/i', '', $name);
        $name = preg_replace('/[^a-z0-9\-_]+/', '-', $name);
        $name = trim($name, '-_');
        if ($name === '') {
            $name = 'bank';
        }

        // Always prefix timestamp so deploy/stock themes never collide and names stay unique.
        $filename = $timestamp . '-' . $name . '.css';
        $path = $this->customPath() . DIRECTORY_SEPARATOR . $filename;
        $i = 1;
        while (is_file($path)) {
            $filename = $timestamp . '-' . $name . '-' . $i . '.css';
            $path = $this->customPath() . DIRECTORY_SEPARATOR . $filename;
            $i++;
        }

        return $filename;
    }

    /**
     * @param  array<string,string>  $colors
     * @return array<string,string>
     */
    protected function normalizeColorInput(array $colors): array
    {
        $out = [];
        foreach (self::COLOR_KEYS as $key) {
            $hex = $colors[$key] ?? null;
            if (!$hex || !preg_match('/^#?[0-9a-fA-F]{6}$/', $hex)) {
                throw new InvalidArgumentException("Invalid color for {$key}.");
            }
            $out[$key] = '#' . ltrim($hex, '#');
        }

        return $out;
    }

    /**
     * @param  array<string,string>  $colors  keys = BADGE_KEYS
     * @return array<string,string>
     */
    protected function normalizeBadgeColorInput(array $colors): array
    {
        $out = [];
        foreach (self::BADGE_KEYS as $key) {
            $hex = $colors[$key] ?? null;
            if (!$hex || !preg_match('/^#?[0-9a-fA-F]{6}$/', $hex)) {
                throw new InvalidArgumentException("Invalid badge color for badge-{$key}.");
            }
            $out[$key] = '#' . ltrim($hex, '#');
        }

        return $out;
    }

    /**
     * Normalize text/ink colors; fall back to auto contrast against the surface color.
     *
     * @param  array<string,string>|null  $textColors
     * @param  array<string,string>  $surfaceColors
     * @param  string[]  $keys
     * @return array<string,string>
     */
    protected function normalizeTextColorInput(?array $textColors, array $surfaceColors, array $keys = self::COLOR_KEYS): array
    {
        $textColors = $textColors ?: [];
        $out = [];
        foreach ($keys as $key) {
            $hex = $textColors[$key] ?? null;
            if ($hex && preg_match('/^#?[0-9a-fA-F]{6}$/', $hex)) {
                $out[$key] = '#' . ltrim($hex, '#');
            } else {
                $out[$key] = $this->contrastInk($surfaceColors[$key] ?? '#000000');
            }
        }

        return $out;
    }

    /**
     * Normalize button hover colors; default to darkened surface BG.
     *
     * @param  array<string,string>|null  $hoverColors
     * @param  array<string,string>  $surfaceColors
     * @return array<string,string>
     */
    protected function normalizeHoverColorInput(?array $hoverColors, array $surfaceColors): array
    {
        $hoverColors = $hoverColors ?: [];
        $out = [];
        foreach (self::COLOR_KEYS as $key) {
            $hex = $hoverColors[$key] ?? null;
            if ($hex && preg_match('/^#?[0-9a-fA-F]{6}$/', $hex)) {
                $out[$key] = '#' . ltrim($hex, '#');
            } else {
                $out[$key] = $this->adjustBrightness($surfaceColors[$key] ?? '#000000', -0.12);
            }
        }

        return $out;
    }

    protected function matchCssVar(string $css, string $varName): ?string
    {
        $pattern = '/' . preg_quote($varName, '/') . '\s*:\s*(#[0-9a-fA-F]{3,8}|black|white)\s*;/i';
        if (!preg_match($pattern, $css, $m)) {
            return null;
        }

        return $this->normalizeHex($m[1]);
    }

    protected function normalizeHex(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === 'black') {
            return '#000000';
        }
        if ($value === 'white') {
            return '#ffffff';
        }
        $value = ltrim($value, '#');
        if (strlen($value) === 3) {
            $value = $value[0] . $value[0] . $value[1] . $value[1] . $value[2] . $value[2];
        }

        return '#' . $value;
    }

    /**
     * @param  array<string,mixed>  $oldPalette
     * @param  array<string,string>  $newColors
     * @return array<string,string> lowercase old hex => new hex
     */
    protected function buildReplacementMap(array $oldPalette, array $newColors): array
    {
        $map = [];

        $families = [
            'primary' => 'primary',
            'success' => 'success',
            'info' => 'info',
            'warning' => 'warning',
            'danger' => 'danger',
            'fusion' => 'dark',
        ];

        foreach ($families as $family => $colorKey) {
            $oldBase = $oldPalette[$colorKey === 'dark' ? 'dark' : $colorKey] ?? null;
            $newBase = $newColors[$colorKey] ?? null;
            if (!$oldBase || !$newBase) {
                continue;
            }

            $oldShades = $oldPalette['shades'][$family] ?? [];
            $newShades = $this->generateShadeScale($newBase);

            // Map 500 / main first
            $map[strtolower($oldBase)] = strtolower($newBase);
            if (!empty($oldShades[500])) {
                $map[strtolower($oldShades[500])] = strtolower($newShades[500]);
            }

            foreach ([50, 100, 200, 300, 400, 500, 600, 700, 800, 900] as $step) {
                if (empty($oldShades[$step])) {
                    continue;
                }
                $map[strtolower($oldShades[$step])] = strtolower($newShades[$step]);
            }
        }

        if (!empty($oldPalette['secondary']) && !empty($newColors['secondary'])) {
            $map[strtolower($oldPalette['secondary'])] = strtolower($newColors['secondary']);
        }

        return $map;
    }

    /**
     * @param  array<string,string>  $map
     */
    protected function applyReplacements(string $css, array $map): string
    {
        // Longest keys first to avoid partial overlaps (unlikely with full hex).
        uksort($map, static function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        return preg_replace_callback('/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})\b/', function ($m) use ($map) {
            $normalized = strtolower($this->normalizeHex($m[0]));
            if (isset($map[$normalized])) {
                return $map[$normalized];
            }

            return $m[0];
        }, $css) ?? $css;
    }

    /**
     * Append definitive component overrides so buttons (incl. hover), alerts,
     * badges, and text/bg utilities beat vendors.bundle defaults.
     *
     * @param  array<string,string>  $colors
     * @param  array<string,string>  $textColors
     * @param  array<string,string>  $badgeColors
     * @param  array<string,string>  $badgeTextColors
     * @param  array<string,string>|null  $hoverColors
     */
    public function appendThemeOverrides(
        string $css,
        array $colors,
        array $textColors,
        array $badgeColors,
        array $badgeTextColors,
        ?array $hoverColors = null
    ): string {
        // Strip prior generated blocks so updates do not stack.
        $css = preg_replace(
            '/\n\/\* === portal-master (?:badge|theme) overrides \(auto\) === \*\/.*$/s',
            '',
            $css
        ) ?? $css;

        $colors = $this->normalizeColorInput($colors);
        $textColors = $this->normalizeTextColorInput($textColors, $colors);
        $hoverColors = $this->normalizeHoverColorInput($hoverColors, $colors);
        $badgeColors = $this->normalizeBadgeColorInput($badgeColors);
        $badgeTextColors = $this->normalizeTextColorInput($badgeTextColors, $badgeColors, self::BADGE_KEYS);

        $lines = [
            '',
            '/* === portal-master theme overrides (auto) === */',
            '/* Beats vendors.bundle.css button/badge/alert hardcoded colors (incl. hover). */',
            ':root {',
        ];

        foreach (self::COLOR_KEYS as $key) {
            $lines[] = "  --pm-{$key}: {$colors[$key]};";
            $lines[] = "  --pm-{$key}-text: {$textColors[$key]};";
            $lines[] = "  --pm-{$key}-hover: {$hoverColors[$key]};";
        }
        foreach (self::BADGE_KEYS as $key) {
            $lines[] = "  --pm-badge-{$key}: {$badgeColors[$key]};";
            $lines[] = "  --pm-badge-{$key}-text: {$badgeTextColors[$key]};";
        }
        $lines[] = '}';
        $lines[] = '';

        // Buttons + hover/focus/active (palette)
        foreach (self::COLOR_KEYS as $name) {
            $hex = $colors[$name];
            $text = $textColors[$name];
            $hover = $hoverColors[$name];
            $active = $this->adjustBrightness($hover, -0.08);
            $borderHover = $this->adjustBrightness($hover, -0.04);
            $rgb = $this->hexToRgbCsv($hex);
            $rgbHover = $this->hexToRgbCsv($hover);

            $lines[] = ".btn.btn-{$name},";
            $lines[] = ".btn-{$name} {";
            $lines[] = "  color: {$text} !important;";
            $lines[] = "  background-color: {$hex} !important;";
            $lines[] = "  border-color: {$hex} !important;";
            $lines[] = "  -webkit-box-shadow: 0 2px 6px 0 rgba({$rgb}, 0.5) !important;";
            $lines[] = "          box-shadow: 0 2px 6px 0 rgba({$rgb}, 0.5) !important; }";
            $lines[] = ".btn.btn-{$name}:hover, .btn-{$name}:hover,";
            $lines[] = ".btn.btn-{$name}:focus, .btn-{$name}:focus,";
            $lines[] = ".btn.btn-{$name}.focus, .btn-{$name}.focus {";
            $lines[] = "  color: {$text} !important;";
            $lines[] = "  background-color: {$hover} !important;";
            $lines[] = "  border-color: {$borderHover} !important; }";
            $lines[] = ".btn.btn-{$name}:focus, .btn-{$name}:focus,";
            $lines[] = ".btn.btn-{$name}.focus, .btn-{$name}.focus {";
            $lines[] = "  -webkit-box-shadow: 0 0 0 0.2rem rgba({$rgbHover}, 0.5) !important;";
            $lines[] = "          box-shadow: 0 0 0 0.2rem rgba({$rgbHover}, 0.5) !important; }";
            $lines[] = ".btn.btn-{$name}:not(:disabled):not(.disabled):active,";
            $lines[] = ".btn.btn-{$name}:not(:disabled):not(.disabled).active,";
            $lines[] = ".btn-{$name}:not(:disabled):not(.disabled):active,";
            $lines[] = ".btn-{$name}:not(:disabled):not(.disabled).active,";
            $lines[] = ".show > .btn-{$name}.dropdown-toggle {";
            $lines[] = "  color: {$text} !important;";
            $lines[] = "  background-color: {$active} !important;";
            $lines[] = "  border-color: {$active} !important; }";
            $lines[] = ".btn.btn-{$name}.disabled, .btn.btn-{$name}:disabled,";
            $lines[] = ".btn-{$name}.disabled, .btn-{$name}:disabled {";
            $lines[] = "  color: {$text} !important;";
            $lines[] = "  background-color: {$hex} !important;";
            $lines[] = "  border-color: {$hex} !important; }";

            // Outline buttons
            $lines[] = ".btn.btn-outline-{$name}, .btn-outline-{$name} {";
            $lines[] = "  color: {$hex} !important;";
            $lines[] = "  background-color: transparent !important;";
            $lines[] = "  border-color: {$hex} !important; }";
            $lines[] = ".btn.btn-outline-{$name}:hover, .btn-outline-{$name}:hover,";
            $lines[] = ".btn.btn-outline-{$name}:focus, .btn-outline-{$name}:focus {";
            $lines[] = "  color: {$text} !important;";
            $lines[] = "  background-color: {$hover} !important;";
            $lines[] = "  border-color: {$hover} !important; }";
        }

        // btn-link uses primary (+ hover)
        $lines[] = ".btn-link { color: {$colors['primary']} !important; }";
        $lines[] = ".btn-link:hover { color: {$hoverColors['primary']} !important; }";
        $lines[] = '';

        // Alerts / error messages — same solid BG + text as buttons
        foreach (self::COLOR_KEYS as $name) {
            $hex = $colors[$name];
            $text = $textColors[$name];

            $lines[] = ".alert.alert-{$name}, .alert-{$name} {";
            $lines[] = "  color: {$text} !important;";
            $lines[] = "  background-color: {$hex} !important;";
            $lines[] = "  border-color: {$hex} !important; }";
            $lines[] = ".alert.alert-{$name} .alert-link, .alert-{$name} .alert-link,";
            $lines[] = ".alert.alert-{$name} a, .alert-{$name} a {";
            $lines[] = "  color: {$text} !important;";
            $lines[] = "  text-decoration: underline; }";
            $lines[] = ".alert.alert-{$name} hr, .alert-{$name} hr {";
            $lines[] = "  border-top-color: {$text} !important; }";
            $lines[] = ".alert.alert-{$name} .close, .alert-{$name} .close {";
            $lines[] = "  color: {$text} !important;";
            $lines[] = "  text-shadow: none !important;";
            $lines[] = "  opacity: 0.85; }";
        }
        $lines[] = '';

        // Text / bg utilities
        foreach (self::COLOR_KEYS as $name) {
            $hex = $colors[$name];
            $text = $textColors[$name];
            $hover = $hoverColors[$name];
            $lines[] = ".text-{$name} { color: {$hex} !important; }";
            $lines[] = "a.text-{$name}:hover, a.text-{$name}:focus { color: {$hover} !important; }";
            $lines[] = ".bg-{$name} {";
            $lines[] = "  background-color: {$hex} !important;";
            $lines[] = "  color: {$text} !important; }";
            $lines[] = "a.bg-{$name}:hover, a.bg-{$name}:focus,";
            $lines[] = "button.bg-{$name}:hover, button.bg-{$name}:focus {";
            $lines[] = "  background-color: {$hover} !important;";
            $lines[] = "  color: {$text} !important; }";
        }
        $lines[] = '';

        // Badges (independent palette + text)
        foreach (self::BADGE_KEYS as $name) {
            $hex = $badgeColors[$name];
            $text = $badgeTextColors[$name];
            $hover = $this->adjustBrightness($hex, -0.12);
            $rgb = $this->hexToRgbCsv($hex);
            $lines[] = ".badge.badge-{$name},";
            $lines[] = "span.badge.badge-{$name},";
            $lines[] = "a.badge.badge-{$name},";
            $lines[] = ".badge-{$name} {";
            $lines[] = "  color: {$text} !important;";
            $lines[] = "  background-color: {$hex} !important; }";
            $lines[] = "a.badge.badge-{$name}:hover, a.badge.badge-{$name}:focus,";
            $lines[] = "a.badge-{$name}:hover, a.badge-{$name}:focus {";
            $lines[] = "  color: {$text} !important;";
            $lines[] = "  background-color: {$hover} !important; }";
            $lines[] = "a.badge.badge-{$name}:focus, a.badge.badge-{$name}.focus,";
            $lines[] = "a.badge-{$name}:focus, a.badge-{$name}.focus {";
            $lines[] = "  outline: 0;";
            $lines[] = "  -webkit-box-shadow: 0 0 0 0.2rem rgba({$rgb}, 0.5) !important;";
            $lines[] = "          box-shadow: 0 0 0 0.2rem rgba({$rgb}, 0.5) !important; }";
        }

        $lines[] = '';

        return rtrim($css) . "\n" . implode("\n", $lines);
    }

    /**
     * Back-compat wrapper — rebuilds full theme override block.
     *
     * @param  array<string,string>|null  $badgeColors
     */
    public function appendBadgeOverrides(string $css, array $badgeColors): string
    {
        $palette = [];
        foreach (self::COLOR_KEYS as $key) {
            $palette[$key] = $badgeColors[$key] ?? '#000000';
        }
        // Prefer :root theme vars already present in $css when available.
        foreach (self::COLOR_KEYS as $key) {
            $var = $key === 'dark' ? '--theme-dark' : '--theme-' . $key;
            $found = $this->matchCssVar($css, $var);
            if ($found) {
                $palette[$key] = $found;
            }
        }
        $texts = $this->normalizeTextColorInput(null, $palette);
        $hovers = $this->normalizeHoverColorInput(null, $palette);
        $badgeTexts = $this->normalizeTextColorInput(null, $badgeColors, self::BADGE_KEYS);

        return $this->appendThemeOverrides($css, $palette, $texts, $badgeColors, $badgeTexts, $hovers);
    }

    /**
     * Ensure an existing custom theme file has the full override block.
     *
     * @param  array<string,string>|null  $badgeColors
     */
    public function ensureBadgeOverrides(string $theme, ?array $badgeColors = null): void
    {
        $this->ensureThemeOverrides($theme, null, null, $badgeColors, null, null);
    }

    /**
     * Ensure custom theme has buttons/alerts/badges overrides with text colors.
     *
     * @param  array<string,string>|null  $colors
     * @param  array<string,string>|null  $textColors
     * @param  array<string,string>|null  $badgeColors
     * @param  array<string,string>|null  $badgeTextColors
     * @param  array<string,string>|null  $hoverColors
     */
    public function ensureThemeOverrides(
        string $theme,
        ?array $colors = null,
        ?array $textColors = null,
        ?array $badgeColors = null,
        ?array $badgeTextColors = null,
        ?array $hoverColors = null
    ): void {
        if (!$this->isCustomTheme($theme)) {
            return;
        }

        $path = $this->resolveThemePath($theme);
        $css = file_get_contents($path);
        if ($css === false) {
            return;
        }

        $palette = $this->extractPalette($theme);
        $colors = $colors ?: collect(self::COLOR_KEYS)->mapWithKeys(function ($key) use ($palette) {
            return [$key => $palette[$key] ?? '#000000'];
        })->all();
        $badgeColors = $badgeColors ?: $this->extractBadgeColors($theme, $palette);
        $textColors = $textColors ?: $this->extractTextColors($theme, $palette);
        $badgeTextColors = $badgeTextColors ?: $this->extractBadgeTextColors($theme, $badgeColors);
        $hoverColors = $hoverColors ?: $this->extractHoverColors($theme, $palette);

        $css = $this->appendThemeOverrides($css, $colors, $textColors, $badgeColors, $badgeTextColors, $hoverColors);
        file_put_contents($path, $css);
    }

    public function mixWithWhite(string $hex, float $whiteAmount): string
    {
        $hex = ltrim($this->normalizeHex($hex), '#');
        $whiteAmount = max(0.0, min(1.0, $whiteAmount));
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $r = (int) round($r + (255 - $r) * $whiteAmount);
        $g = (int) round($g + (255 - $g) * $whiteAmount);
        $b = (int) round($b + (255 - $b) * $whiteAmount);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    public function isLight(string $hex): bool
    {
        $hex = ltrim($this->normalizeHex($hex), '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $brightness >= 160;
    }

    /**
     * Light text on dark bg, dark text on light bg (e.g. warning yellow).
     */
    public function contrastInk(string $hex): string
    {
        $hex = ltrim($this->normalizeHex($hex), '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $brightness >= 160 ? '#212529' : '#ffffff';
    }

    /**
     * @param  float  $amount  negative darkens, positive lightens (approx -1..1)
     */
    public function adjustBrightness(string $hex, float $amount): string
    {
        [$h, $s, $l] = $this->hexToHsl($hex);
        $l = max(0.0, min(1.0, $l + $amount));

        return $this->hslToHex($h, $s, $l);
    }

    /**
     * Ensure :root variables and rgb companions match the chosen palette.
     *
     * @param  array<string,mixed>  $oldPalette
     * @param  array<string,string>  $newColors
     */
    protected function rewriteRootBlock(string $css, array $oldPalette, array $newColors): string
    {
        $shades = [
            'primary' => $this->generateShadeScale($newColors['primary']),
            'success' => $this->generateShadeScale($newColors['success']),
            'info' => $this->generateShadeScale($newColors['info']),
            'warning' => $this->generateShadeScale($newColors['warning']),
            'danger' => $this->generateShadeScale($newColors['danger']),
            'fusion' => $this->generateShadeScale($newColors['dark']),
        ];

        $replacements = [
            '--theme-primary' => $newColors['primary'],
            '--theme-secondary' => $newColors['secondary'],
            '--theme-success' => $newColors['success'],
            '--theme-info' => $newColors['info'],
            '--theme-warning' => $newColors['warning'],
            '--theme-danger' => $newColors['danger'],
            '--theme-dark' => $newColors['dark'],
            '--theme-rgb-primary' => $this->hexToRgbCsv($newColors['primary']),
            '--theme-rgb-success' => $this->hexToRgbCsv($newColors['success']),
            '--theme-rgb-info' => $this->hexToRgbCsv($newColors['info']),
            '--theme-rgb-warning' => $this->hexToRgbCsv($newColors['warning']),
            '--theme-rgb-danger' => $this->hexToRgbCsv($newColors['danger']),
            '--theme-rgb-fusion' => $this->hexToRgbCsv($newColors['dark']),
        ];

        foreach ($shades as $family => $scale) {
            foreach ($scale as $step => $hex) {
                $replacements["--theme-{$family}-{$step}"] = $hex;
            }
        }

        foreach ($replacements as $var => $value) {
            $css = preg_replace(
                '/' . preg_quote($var, '/') . '\s*:\s*[^;]+;/i',
                $var . ': ' . $value . ';',
                $css,
                1
            ) ?? $css;
        }

        return $css;
    }

    /**
     * @return array<int,string> steps 50..900
     */
    public function generateShadeScale(string $hex): array
    {
        $hex = $this->normalizeHex($hex);
        [$h, $s, $l] = $this->hexToHsl($hex);

        // Approximate SmartAdmin-style scale around 500 = base
        $lightness = [
            50 => min(0.92, $l + 0.40),
            100 => min(0.88, $l + 0.32),
            200 => min(0.82, $l + 0.24),
            300 => min(0.76, $l + 0.16),
            400 => min(0.70, $l + 0.08),
            500 => $l,
            600 => max(0.08, $l - 0.08),
            700 => max(0.06, $l - 0.16),
            800 => max(0.04, $l - 0.24),
            900 => max(0.02, $l - 0.32),
        ];

        $out = [];
        foreach ($lightness as $step => $nl) {
            $out[$step] = $this->hslToHex($h, $s, $nl);
        }

        return $out;
    }

    protected function hexToRgbCsv(string $hex): string
    {
        $hex = ltrim($this->normalizeHex($hex), '#');
        return hexdec(substr($hex, 0, 2)) . ',' . hexdec(substr($hex, 2, 2)) . ',' . hexdec(substr($hex, 4, 2));
    }

    /**
     * @return array{0:float,1:float,2:float} h 0-360, s 0-1, l 0-1
     */
    protected function hexToHsl(string $hex): array
    {
        $hex = ltrim($this->normalizeHex($hex), '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;

        if ($d < 0.00001) {
            return [0.0, 0.0, $l];
        }

        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        switch ($max) {
            case $r:
                $h = (($g - $b) / $d) + ($g < $b ? 6 : 0);
                break;
            case $g:
                $h = (($b - $r) / $d) + 2;
                break;
            default:
                $h = (($r - $g) / $d) + 4;
                break;
        }
        $h *= 60;

        return [$h, $s, $l];
    }

    protected function hslToHex(float $h, float $s, float $l): string
    {
        $h = fmod($h, 360);
        if ($h < 0) {
            $h += 360;
        }
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        if ($h < 60) {
            [$r, $g, $b] = [$c, $x, 0.0];
        } elseif ($h < 120) {
            [$r, $g, $b] = [$x, $c, 0.0];
        } elseif ($h < 180) {
            [$r, $g, $b] = [0.0, $c, $x];
        } elseif ($h < 240) {
            [$r, $g, $b] = [0.0, $x, $c];
        } elseif ($h < 300) {
            [$r, $g, $b] = [$x, 0.0, $c];
        } else {
            [$r, $g, $b] = [$c, 0.0, $x];
        }

        return sprintf(
            '#%02x%02x%02x',
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255)
        );
    }
}
