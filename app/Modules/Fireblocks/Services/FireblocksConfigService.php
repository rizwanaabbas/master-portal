<?php

namespace App\Modules\Fireblocks\Services;

use App\Helpers\Common;
use App\Helpers\SecretKeyPathHelper;
use App\Models\Module;
use App\Models\ModuleConfiguration;
use RuntimeException;

class FireblocksConfigService
{
    private static ?array $resolved = null;

    /**
     * Return the Fireblocks module config array ready for FireblocksClient.
     *
     * @throws RuntimeException if the module is disabled or config is missing
     */
    public static function get(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $module = Module::where('name', 'Fireblocks')->first();

        if (!$module || !$module->is_enabled) {
            throw new RuntimeException('Fireblocks module is not enabled.');
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$record) {
            throw new RuntimeException('Fireblocks module configuration not found.');
        }

        $config = unserialize($record->configurations);

        if (!is_array($config)) {
            throw new RuntimeException('Fireblocks module configuration is corrupt.');
        }

        if(env('APP_ENV_TYPE') === 'api') {
            $config['api_key'] = $config['mobile_api_key'];
            $config['api_secret'] = $config['mobile_api_secret'];
            $config['fire_blocks_secret_key_path'] = $config['mobile_api_secret_path'];
        } else {
            $config['api_key'] = $config['admin_api_key'];
            $config['api_secret'] = $config['admin_api_secret'];
            $config['fire_blocks_secret_key_path'] = $config['admin_api_secret_path'];
        }

        self::$resolved = [
            'base_url'                 => self::val($config, 'base_url', 'https://api.fireblocks.io'),
            'api_key'                  => self::val($config, 'api_key'),
            'api_secret'               => self::val($config, 'api_secret'),
            'api_secret_path'          => self::resolvePath(self::val($config, 'fire_blocks_secret_key_path')),
            'admin_api_secret_path'    => self::resolvePath(self::val($config, 'admin_api_secret_path')),
            'mobile_api_secret_path'   => self::resolvePath(self::val($config, 'mobile_api_secret_path')),
            'source_vault_account_id'  => self::val($config, 'source_vault_account_id'),
            'omnibus_vault_id'         => self::normalizeOmnibusVaultId(self::val($config, 'omnibus_vault_id', '0')),
            'wallet_pool_id'           => trim((string) self::val($config, 'wallet_pool_id', '')),
            'webhook_verify_signature' => (bool) self::val($config, 'webhook_verify_signature', true),
            'webhook_public_key_type'  => self::val($config, 'webhook_public_key_type', 'production'),
            'webhook_custom_public_key' => self::val($config, 'webhook_custom_public_key', ''),
            'travel_rules_enabled'     => (bool) self::val($config, 'travel_rules_enabled', false),
            'default_fee_level'        => self::val($config, 'default_fee_level', 'MEDIUM'),
            'vault_hidden_on_ui'       => (bool) self::val($config, 'vault_hidden_on_ui', false),
            'vault_auto_fuel'          => (bool) self::val($config, 'vault_auto_fuel', true),
            'show_vaults_menu'         => (bool) self::val($config, 'show_vaults_menu', false),
            'whitelist_api_key'        => self::val($config, 'whitelist_address_api'),
            'whitelist_api_secret_path' => self::resolvePath(self::val($config, 'whitelist_address_api_secret_path')),
            'timeout'                  => 30,
            'connect_timeout'          => 10,
            'max_retries'              => 3,
            'retry_delay'              => 500,
            'debug'                    => false,
            'proxy'                    => null,
            'headers'                  => [],
        ];

        return self::$resolved;
    }

    /**
     * Return the source vault account ID.
     */
    public static function sourceVaultAccountId(): string
    {
        try {
            return trim((string) (self::get()['source_vault_account_id'] ?? ''));
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Vault account used only for omnibus balance fetch and sync. Default 0.
     */
    public static function omnibusVaultId(): string
    {
        try {
            return self::normalizeOmnibusVaultId(self::get()['omnibus_vault_id'] ?? '0');
        } catch (\Throwable) {
            return '0';
        }
    }

    public static function normalizeOmnibusVaultId(mixed $value): string
    {
        $id = trim((string) $value);

        return $id !== '' ? $id : '0';
    }

    /**
     * Fireblocks Wallet Pool tag UUID for non-UTXO withdrawal sources (WALLET_POOL).
     */
    public static function walletPoolId(): string
    {
        try {
            return trim((string) (self::get()['wallet_pool_id'] ?? ''));
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Whether the Travel Rule fields should be collected for beneficiaries.
     * Safe to call outside an enabled-module context (returns false).
     */
    public static function travelRulesEnabled(): bool
    {
        try {
            return (bool) (self::get()['travel_rules_enabled'] ?? false);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Default fee level (LOW/MEDIUM/HIGH) used when a transaction request doesn't specify one.
     */
    public static function defaultFeeLevel(): string
    {
        try {
            return self::get()['default_fee_level'] ?? 'MEDIUM';
        } catch (\Throwable $e) {
            return 'MEDIUM';
        }
    }

    /**
     * Default hiddenOnUI flag applied when creating new vault accounts.
     */
    public static function vaultHiddenOnUi(): bool
    {
        try {
            return (bool) (self::get()['vault_hidden_on_ui'] ?? false);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Default autoFuel flag applied when creating new vault accounts.
     */
    public static function vaultAutoFuel(): bool
    {
        try {
            return (bool) (self::get()['vault_auto_fuel'] ?? true);
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * Whether the Fireblocks Vaults menu item should be shown in the left nav.
     */
    public static function showVaultsMenu(): bool
    {
        try {
            return (bool) (self::get()['show_vaults_menu'] ?? false);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Whitelist / external-wallet API key (never falls back to admin credentials).
     */
    public static function whitelistApiKey(): string
    {
        try {
            return trim((string) (self::get()['whitelist_api_key'] ?? ''));
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Filesystem path to the whitelist API secret PEM.
     */
    public static function whitelistApiSecretPath(): string
    {
        try {
            return trim((string) (self::get()['whitelist_api_secret_path'] ?? ''));
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Whether dedicated whitelist credentials are configured and the secret file exists.
     */
    public static function hasWhitelistCredentials(): bool
    {
        $key = self::whitelistApiKey();
        $path = self::whitelistApiSecretPath();

        return $key !== '' && $path !== '' && file_exists($path);
    }

    /**
     * Lightweight Fireblocks client-rollout settings. Does not require API credentials.
     *
     * @return array{enabled: bool, mode: string, client_ids: int[]}
     */
    public static function tryClientEnablement(): array
    {
        $module = Module::where('name', 'Fireblocks')->first();

        if (!$module || !$module->is_enabled) {
            return ['enabled' => false, 'mode' => 'all', 'client_ids' => []];
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$record) {
            return ['enabled' => true, 'mode' => 'all', 'client_ids' => []];
        }

        $config = unserialize($record->configurations);
        if (!is_array($config)) {
            return ['enabled' => true, 'mode' => 'all', 'client_ids' => []];
        }

        $mode = strtolower((string) self::val($config, 'enable_for_clients', 'all'));
        if ($mode !== 'specific') {
            $mode = 'all';
        }

        $raw = (string) self::val($config, 'enable_for_client_ids', '');
        $ids = array_values(array_unique(array_filter(
            array_map('intval', preg_split('/[\s,]+/', $raw) ?: []),
            fn ($id) => $id > 0
        )));

        return ['enabled' => true, 'mode' => $mode, 'client_ids' => $ids];
    }

    /**
     * Whether the new Fireblocks crypto UI should be shown for this user.
     */
    public static function isNewCryptoEnabledForUser(int $userId): bool
    {
        $settings = self::tryClientEnablement();

        if (!$settings['enabled']) {
            return false;
        }

        if ($settings['mode'] === 'all') {
            return true;
        }

        $userClientIds = Common::getClientIdsByUserId($userId);

        return !empty(array_intersect($userClientIds, $settings['client_ids']));
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

    private static function resolvePath(mixed $path): string
    {
        return SecretKeyPathHelper::resolve((string) $path);
    }
}
