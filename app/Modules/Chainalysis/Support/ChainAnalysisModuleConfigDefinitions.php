<?php

namespace App\Modules\Chainalysis\Support;

class ChainAnalysisModuleConfigDefinitions
{
    public static function all(): array
    {
        return [
            'api_key' => [
                'hint' => 'Chainalysis API token used for address entity screening (KYT). Stored as a secret in module configuration.',
            ],
            'base_url' => [
                'hint' => 'Chainalysis public API base URL. Production typically uses https://public.chainalysis.com/api/v1.',
                'sample' => 'https://public.chainalysis.com/api/v1',
            ],
            'risk_passing_criteria' => [
                'value' => 'low',
                'type' => 'select',
                'label' => 'Risk Passing Criteria',
                'hint' => 'Maximum risk level that still passes compliance. Lower severities always pass too: Severe → all levels; High → High, Medium, Low; Medium → Medium and Low; Low → Low only. Addresses above this threshold are blocked when adding beneficiaries and flagged in transaction monitoring.',
                'options' => [
                    'low' => 'Low — only Low risk passes',
                    'medium' => 'Medium — Medium and Low pass',
                    'high' => 'High — High, Medium, and Low pass',
                    'severe' => 'Severe — all levels pass (including Severe)',
                ],
            ],
            'sanctions_screening_enabled' => [
                'value' => '1',
                'type' => 'checkbox',
                'label' => 'Sanctions Screening Enabled',
                'hint' => 'When enabled, wallet addresses are screened via Chainalysis when whitelisting beneficiaries and on related monitoring flows. When disabled, Chainalysis screening is skipped (module must still be enabled for other settings).',
            ],
        ];
    }

    public static function mergeInto(array $existing): array
    {
        foreach (self::all() as $key => $definition) {
            if (!array_key_exists($key, $existing)) {
                $existing[$key] = array_merge(
                    ['value' => $definition['value'] ?? '', 'type' => 'text', 'label' => $key],
                    $definition
                );
                continue;
            }

            foreach (['label', 'type', 'hint', 'sample', 'placeholder', 'options'] as $prop) {
                if (isset($definition[$prop])) {
                    if ($key === 'risk_passing_criteria' || $key === 'sanctions_screening_enabled') {
                        $existing[$key][$prop] = $definition[$prop];
                    } elseif (empty($existing[$key][$prop])) {
                        $existing[$key][$prop] = $definition[$prop];
                    }
                }
            }

            if ($key === 'risk_passing_criteria') {
                $existing[$key]['value'] = self::normalizeRiskThresholdValue(
                    (string) ($existing[$key]['value'] ?? 'low')
                );
            }

            if ($key === 'sanctions_screening_enabled' && !isset($existing[$key]['value'])) {
                $existing[$key]['value'] = $definition['value'];
            }
        }

        return $existing;
    }

    /**
     * Convert legacy comma-separated passing list or label text to a single threshold key.
     */
    public static function normalizeRiskThresholdValue(string $raw): string
    {
        $raw = strtolower(trim($raw));
        if ($raw === '') {
            return 'low';
        }

        if (!str_contains($raw, ',')) {
            $single = preg_replace('/[^a-z]/', '', $raw);
            if (in_array($single, ['low', 'medium', 'high', 'severe'], true)) {
                return $single;
            }
        }

        $parts = array_filter(array_map(
            fn ($v) => strtolower(trim(preg_replace('/[^a-z]/', '', $v))),
            explode(',', $raw)
        ));

        $rank = ['low' => 1, 'medium' => 2, 'high' => 3, 'severe' => 4];
        $max = 1;
        foreach ($parts as $part) {
            if (isset($rank[$part])) {
                $max = max($max, $rank[$part]);
            }
        }

        return array_search($max, $rank, true) ?: 'low';
    }
}
