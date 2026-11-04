<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Helpers\Common;
use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Modules\FireblocksTransactionsMonitoring\Support\MonitoringConfigValidator;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MonitoringConfigService
{
    private static ?array $resolved = null;

    public static function get(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $module = Module::where('name', 'FireblocksTransactionsMonitoring')->first();

        if (!$module || !$module->is_enabled) {
            throw new RuntimeException('FireblocksTransactionsMonitoring module is not enabled.');
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$record) {
            throw new RuntimeException('FireblocksTransactionsMonitoring configuration not found.');
        }

        $config = unserialize($record->configurations);

        if (!is_array($config)) {
            throw new RuntimeException('FireblocksTransactionsMonitoring configuration is corrupt.');
        }

        $sanitized = MonitoringConfigValidator::sanitize([
            'monitoring_enabled' => self::bool($config, 'monitoring_enabled'),
            'include_aquanow_buy_sell' => self::bool($config, 'include_aquanow_buy_sell'),
            'webhook_stuck_alert_enabled' => self::bool($config, 'webhook_stuck_alert_enabled', true),
            'polling_fallback_enabled' => self::bool($config, 'polling_fallback_enabled', true),
            'polling_interval_minutes' => max(3, (int) self::val($config, 'polling_interval_minutes', 5)),
            'stuck_confirming_threshold_minutes' => (int) self::val($config, 'stuck_confirming_threshold_minutes', 15),
            'stuck_signature_threshold_minutes' => (int) self::val($config, 'stuck_signature_threshold_minutes', 60),
            'auto_boost_enabled' => self::bool($config, 'auto_boost_enabled'),
            'auto_boost_requires_atc_webhook' => self::bool($config, 'auto_boost_requires_atc_webhook', true),
            'auto_drop_enabled' => self::bool($config, 'auto_drop_enabled'),
            'auto_cpfp_enabled' => self::bool($config, 'auto_cpfp_enabled'),
            'auto_resubmit_mode' => self::val($config, 'auto_resubmit_mode', 'manual'),
            'manual_approval_required' => self::bool($config, 'manual_approval_required', true),
            'dry_run_mode' => self::bool($config, 'dry_run_mode'),
            'boost_fee_level' => self::val($config, 'boost_fee_level', 'HIGH'),
            'boost_gas_price' => self::val($config, 'boost_gas_price', ''),
            'max_auto_boost_attempts' => (int) self::val($config, 'max_auto_boost_attempts', 2),
            'max_auto_drop_attempts' => (int) self::val($config, 'max_auto_drop_attempts', 1),
            'max_resubmit_attempts' => (int) self::val($config, 'max_resubmit_attempts', 1),
            'cpfp_max_age_days' => (int) self::val($config, 'cpfp_max_age_days', 10),
            'monitored_fireblocks_statuses' => self::csv($config, 'monitored_fireblocks_statuses'),
            'retriable_substatuses' => self::csv($config, 'retriable_substatuses'),
            'non_retriable_substatuses' => self::csv($config, 'non_retriable_substatuses'),
            'monitored_asset_ids' => self::csv($config, 'monitored_asset_ids'),
            'monitored_vault_account_ids' => self::csv($config, 'monitored_vault_account_ids'),
            'monitored_directions' => self::val($config, 'monitored_directions', 'outgoing'),
            'resend_webhook_on_stale' => self::bool($config, 'resend_webhook_on_stale', true),
            'stale_webhook_threshold_minutes' => (int) self::val($config, 'stale_webhook_threshold_minutes', 30),
            'notify_on_stuck_detected' => self::bool($config, 'notify_on_stuck_detected', true),
            'notify_on_auto_action' => self::bool($config, 'notify_on_auto_action', true),
            'notification_emails' => self::csv($config, 'notification_emails'),
            'low_balance_alert_recipient_roles' => self::roleIds($config, 'low_balance_alert_recipient_roles'),
            'dashboard_page_size' => (int) self::val($config, 'dashboard_page_size', 15),
            'audit_retention_days' => (int) self::val($config, 'audit_retention_days', 365),
            'boost_eligible_primary_statuses' => self::csv($config, 'boost_eligible_primary_statuses'),
            'drop_eligible_primary_statuses' => self::csv($config, 'drop_eligible_primary_statuses'),
            'resubmit_eligible_primary_statuses' => self::csv($config, 'resubmit_eligible_primary_statuses'),
            'default_filter_monitoring_state' => self::val($config, 'default_filter_monitoring_state', 'all'),
            'dashboard_column_queue' => self::bool($config, 'dashboard_column_queue'),
            'dashboard_column_reattempts' => self::bool($config, 'dashboard_column_reattempts'),
            'dashboard_column_last_reattempt' => self::bool($config, 'dashboard_column_last_reattempt'),
            'dashboard_column_stuck_for' => self::bool($config, 'dashboard_column_stuck_for'),
            'dashboard_column_ledger' => self::bool($config, 'dashboard_column_ledger'),
            'poisoning_deposit_max_amount' => self::val($config, 'poisoning_deposit_max_amount', '1'),
            'poisoning_deposit_asset_max_amounts' => self::val($config, 'poisoning_deposit_asset_max_amounts', ''),
        ]);

        self::$resolved = $sanitized['config'];

        foreach ($sanitized['warnings'] as $warning) {
            Log::debug('Fireblocks monitoring config: ' . $warning);
        }

        return self::$resolved;
    }

    /**
     * Dashboard filter defaults on first load (before sessionStorage overrides).
     *
     * Only monitoring state is configurable. Direction follows Monitored Transaction Directions;
     * Fireblocks status and sub-status default to All.
     *
     * @param  array<string, mixed>|null  $config  Resolved config from tryGet()/get()
     * @return array{monitoring_state: string, fb_status: string, fb_sub_status: string, internal_status: string, direction: string, stuck_only: bool}
     */
    public static function dashboardDefaultFilters(?array $config = null): array
    {
        $config = $config ?? self::tryGet() ?? [];

        $monitoredDirection = (string) ($config['monitored_directions'] ?? 'outgoing');

        return [
            'monitoring_state' => (string) ($config['default_filter_monitoring_state'] ?? 'all'),
            'fb_status' => 'all',
            'fb_sub_status' => 'all',
            'internal_status' => 'all',
            'direction' => match ($monitoredDirection) {
                'incoming' => 'incoming',
                'both' => 'all',
                default => 'outgoing',
            },
            'stuck_only' => false,
            'ledger_scope' => 'ledger',
        ];
    }

    public static function tryGet(): ?array
    {
        try {
            return self::get();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function isActive(): bool
    {
        $config = self::tryGet();

        return $config !== null && !empty($config['monitoring_enabled']);
    }

    public static function includeAquanowBuySell(?array $config = null): bool
    {
        $config = $config ?? self::tryGet() ?? [];

        return !empty($config['include_aquanow_buy_sell']) && Common::isModuleEnabled('Aquanow');
    }

    /**
     * Optional dashboard / detail fields (checkbox module config — default hidden).
     *
     * @return array{
     *     queue: bool,
     *     reattempts: bool,
     *     last_reattempt: bool,
     *     stuck_for: bool,
     *     ledger: bool
     * }
     */
    public static function dashboardColumnVisibility(?array $config = null): array
    {
        $config = $config ?? self::tryGet() ?? [];

        return [
            'queue' => !empty($config['dashboard_column_queue']),
            'reattempts' => !empty($config['dashboard_column_reattempts']),
            'last_reattempt' => !empty($config['dashboard_column_last_reattempt']),
            'stuck_for' => !empty($config['dashboard_column_stuck_for']),
            'ledger' => !empty($config['dashboard_column_ledger']),
        ];
    }

    public static function flush(): void
    {
        self::$resolved = null;
        MonitoringAlertRecipientService::flush();
    }

    private static function val(array $config, string $key, mixed $default = ''): mixed
    {
        return $config[$key]['value'] ?? $default;
    }

    private static function bool(array $config, string $key, bool $default = false): bool
    {
        $value = self::val($config, $key, $default ? '1' : '0');

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    private static function csv(array $config, string $key): array
    {
        $rawValue = self::val($config, $key, '');
        if (is_array($rawValue)) {
            $items = $rawValue;
        } else {
            $raw = trim((string) $rawValue);
            if ($raw === '') {
                return [];
            }
            $items = preg_split('/[\s,]+/', $raw) ?: [];
        }

        return array_values(array_filter(array_map(function ($item) {
            $item = strtoupper(trim(str_replace(' ', '_', (string) $item)));

            return $item !== '' && $item !== 'ARRAY' ? $item : null;
        }, $items)));
    }

    /** @return array<int, int> */
    private static function roleIds(array $config, string $key): array
    {
        $rawValue = self::val($config, $key, '');
        if (is_array($rawValue)) {
            $raw = implode(',', $rawValue);
        } else {
            $raw = trim((string) $rawValue);
        }

        return MonitoringAlertRecipientService::normalizeRoleIds($raw);
    }
}
