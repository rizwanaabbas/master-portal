<?php

namespace App\Modules\Chainalysis\Services;

use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Modules\Chainalysis\Support\ChainAnalysisModuleConfigDefinitions;

class ChainAnalysisConfigService
{
    private static ?array $resolved = null;

    /** @var array<string, int> Severity rank (higher = riskier). */
    private const RISK_RANK = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
        'severe' => 4,
    ];

    /**
     * Return ChainAnalysis config from module configuration DB record.
     *
     * @throws \RuntimeException if the module is disabled or config is missing
     */
    public static function get(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $module = Module::where('name', 'ChainAnalysis')->first();

        if (!$module || !$module->is_enabled) {
            throw new \RuntimeException('ChainAnalysis module is not enabled.');
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$record) {
            throw new \RuntimeException('ChainAnalysis module configuration not found.');
        }

        $config = unserialize($record->configurations);
        //dd($config);
        if (!is_array($config)) {
            throw new \RuntimeException('ChainAnalysis module configuration is corrupt.');
        }

        $thresholdRaw = (string) self::val($config, 'risk_passing_criteria', 'low');
        $passingCriteria = self::expandPassingCriteria($thresholdRaw);

        self::$resolved = [
            'base_url'         => rtrim(self::val($config, 'base_url', 'https://public.chainalysis.com/api/v1'), '/'),
            'api_key'          => self::val($config, 'api_key'),
            'passing_criteria' => $passingCriteria,
            'risk_threshold'   => ChainAnalysisModuleConfigDefinitions::normalizeRiskThresholdValue($thresholdRaw),
            'sanctions_screening_enabled' => self::rawSanctionsScreeningEnabled($config),
        ];

        return self::$resolved;
    }

    /**
     * Returns true if the given risk value is NOT in the passing criteria (i.e. should be blocked).
     * Normalises both sides to lowercase and trims spaces.
     */
    public static function isRisky(?string $risk): bool
    {
        if ($risk === null || $risk === '') {
            return false;
        }

        $config = self::get();

        return !in_array(strtolower(trim($risk)), $config['passing_criteria'], true);
    }

    /**
     * Check whether the ChainAnalysis module is enabled without throwing.
     */
    public static function isEnabled(): bool
    {
        $module = Module::where('name', 'ChainAnalysis')->first();
        return $module && (bool) $module->is_enabled;
    }

    /**
     * When false, skip Chainalysis address screening even if the module is enabled.
     */
    public static function isSanctionsScreeningEnabled(): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        try {
            return (bool) self::get()['sanctions_screening_enabled'];
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<string> Lowercase risk labels that pass for the configured threshold.
     */
    public static function expandPassingCriteria(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['low'];
        }

        if (str_contains($raw, ',')) {
            $legacy = array_values(array_filter(array_map(
                fn ($v) => strtolower(trim($v)),
                explode(',', $raw)
            )));

            return $legacy !== [] ? $legacy : ['low'];
        }

        $threshold = ChainAnalysisModuleConfigDefinitions::normalizeRiskThresholdValue($raw);
        $maxRank = self::RISK_RANK[$threshold] ?? self::RISK_RANK['low'];

        return array_keys(array_filter(
            self::RISK_RANK,
            fn (int $rank) => $rank <= $maxRank
        ));
    }

    /**
     * Clear the in-memory cache (useful in tests).
     */
    public static function flush(): void
    {
        self::$resolved = null;
    }

    private static function val(array $config, string $key, mixed $default = ''): mixed
    {
        return $config[$key]['value'] ?? $default;
    }

    private static function rawSanctionsScreeningEnabled(array $config): bool
    {
        $value = self::val($config, 'sanctions_screening_enabled', '1');

        return (string) $value === '1' || $value === 1 || $value === true;
    }
}
