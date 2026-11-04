<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Support;

use App\Modules\Fireblocks\Support\FireblocksStatusCatalog;

/**
 * Detects and resolves conflicting FireblocksTransactionsMonitoring config values.
 */
class MonitoringConfigValidator
{
    /**
     * Normalize action/sub-status lists and apply priority rules.
     *
     * Priority (highest first):
     * 1. User-configured Boost/Drop/Resubmit textarea — explicit match allows that action (even if also Non-Retriable)
     * 2. non_retriable_substatuses — blocks when not explicitly allowed on that action's textarea
     * 3. retriable_substatuses — fallback for Resubmit only (failed primaries)
     *
     * @param  array<string, mixed>  $config  Resolved config from MonitoringConfigService shape
     * @return array{config: array<string, mixed>, warnings: array<int, string>}
     */
    public static function sanitize(array $config): array
    {
        $warnings = [];
        $config = self::normalizeLists($config);

        $nonRetriable = $config['non_retriable_substatuses'] ?? [];
        $retriable = $config['retriable_substatuses'] ?? [];

        $overlap = array_values(array_intersect($retriable, $nonRetriable));
        if (!empty($overlap)) {
            $warnings[] = 'Removed from Retriable (Non-Retriable wins): ' . implode(', ', $overlap);
            $config['retriable_substatuses'] = array_values(array_diff($retriable, $nonRetriable));
        }

        foreach (['boost_eligible_primary_statuses', 'drop_eligible_primary_statuses', 'resubmit_eligible_primary_statuses'] as $key) {
            $list = $config[$key] ?? [];
            $blocked = array_values(array_intersect($list, $nonRetriable));
            if (!empty($blocked)) {
                $warnings[] = sprintf(
                    '%s includes codes also in Non-Retriable (%s) — they will still work for that action when the tx matches.',
                    self::label($key),
                    implode(', ', $blocked)
                );
            }
        }

        $boost = $config['boost_eligible_primary_statuses'] ?? [];
        $drop = $config['drop_eligible_primary_statuses'] ?? [];
        if (!empty($boost) && !empty($drop) && $boost !== $drop) {
            $onlyBoost = array_diff($boost, $drop);
            $onlyDrop = array_diff($drop, $boost);
            if (!empty($onlyBoost) || !empty($onlyDrop)) {
                $warnings[] = 'Boost-eligible and Drop-eligible lists differ — Drop is EVM-only at runtime; align lists unless intentional.';
            }
        }

        if (empty($config['resubmit_eligible_primary_statuses']) && empty($config['retriable_substatuses'])) {
            $warnings[] = 'Resubmit-eligible and Retriable sub-status lists are both empty — built-in defaults will apply.';
        }

        return ['config' => $config, 'warnings' => $warnings];
    }

    /** @return array<int, string> */
    public static function describeRules(): array
    {
        return [
            'Non-Retriable Sub-Statuses block actions unless the same code is explicitly listed in that action\'s config textarea (Boost / Drop / Resubmit).',
            'Boost/Drop/Resubmit eligibility textareas match primary OR sub-status codes on the transaction.',
            'Retriable Sub-Statuses is a fallback for Resubmit only when the tx primary is FAILED/REJECTED/BLOCKED/TIMEOUT and not covered by the Resubmit-eligible list.',
            'Monitored Primary Statuses only affects stuck detection/polling — not action button visibility.',
            'Empty Boost/Drop/Resubmit textareas use built-in defaults (not Retriable list).',
        ];
    }

    /** @param  array<string, mixed>  $config */
    protected static function normalizeLists(array $config): array
    {
        foreach ([
            'boost_eligible_primary_statuses',
            'drop_eligible_primary_statuses',
            'resubmit_eligible_primary_statuses',
            'retriable_substatuses',
            'non_retriable_substatuses',
        ] as $key) {
            if (!isset($config[$key]) || !is_array($config[$key])) {
                continue;
            }
            $config[$key] = self::normalizeCodes($config[$key]);
        }

        return $config;
    }

    /** @param  array<int, string>  $codes */
    protected static function normalizeCodes(array $codes): array
    {
        $normalized = [];
        foreach ($codes as $code) {
            $upper = strtoupper(trim(str_replace(' ', '_', (string) $code)));
            if ($upper === '' || $upper === 'ARRAY') {
                continue;
            }
            $normalized[] = FireblocksStatusCatalog::normalizeSubstatusCode($upper) ?? $upper;
        }

        return array_values(array_unique($normalized));
    }

    protected static function label(string $key): string
    {
        return match ($key) {
            'boost_eligible_primary_statuses' => 'Boost-eligible list',
            'drop_eligible_primary_statuses' => 'Drop-eligible list',
            'resubmit_eligible_primary_statuses' => 'Resubmit-eligible list',
            default => $key,
        };
    }
}
