<?php

namespace App\Modules\Fireblocks\Support;

/**
 * Canonical Settings → Module Configurations schema for the Fireblocks module.
 */
class FireblocksModuleConfigDefinitions
{
    public static function doc(string $key): string
    {
        return self::docs()[$key] ?? 'https://developers.fireblocks.com/';
    }

    public static function docs(): array
    {
        return [
            'api' => 'https://developers.fireblocks.com/reference/rest-api-authentication',
            'webhooks' => 'https://developers.fireblocks.com/reference/webhooks-best-practices',
            'vaults' => 'https://developers.fireblocks.com/reference/create-vault-account',
            'travel' => 'https://developers.fireblocks.com/docs/travel-rule',
            'fees' => 'https://developers.fireblocks.com/reference/create-transactions#feelevel',
        ];
    }

    public static function all(): array
    {
        return [
            'base_url' => [
                'value' => 'https://api.fireblocks.io',
                'type' => 'text',
                'label' => 'Base URL',
                'hint' => 'Fireblocks REST API host. Use the sandbox URL for test workspaces and production URL for live.',
                'usage' => 'FireblocksConfigService → every Fireblocks SDK/API call from cib-portal and shared client bootstrap.',
                'doc_url' => self::doc('api'),
                'doc_label' => 'Fireblocks API authentication',
            ],
            'admin_api_key' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Admin Portal API Key',
                'hint' => 'API user key for the CIB admin portal (cib-portal) Fireblocks integration.',
                'usage' => 'FireblocksConfigService when APP_ENV_TYPE is not api — vaults, deposits, withdrawals, webhooks, asset sync, beneficiaries.',
                'doc_url' => self::doc('api'),
            ],
            'admin_api_secret' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Admin Portal API Secret',
                'hint' => 'Optional inline secret; prefer Admin Portal API Secret Path for production.',
                'usage' => 'Legacy/alternate to path-based key loading in FireblocksConfigService (portal).',
                'required' => false,
            ],
            'admin_api_secret_path' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Admin Portal API Secret Path',
                'hint' => 'Filesystem path to the Fireblocks API private key (.key) for the admin portal API user.',
                'usage' => 'FireblocksConfigService → api_secret_path for all portal Fireblocks operations.',
                'doc_url' => self::doc('api'),
            ],
            'mobile_api_key' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Mobile API Key',
                'hint' => 'Separate Fireblocks API user for cib-mobile-api (client app withdrawals/deposits).',
                'usage' => 'FireblocksConfigService when APP_ENV_TYPE is api — mobile-api Fireblocks module.',
                'doc_url' => self::doc('api'),
            ],
            'mobile_api_secret' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Mobile API Secret',
                'hint' => 'Optional inline secret for mobile API user; prefer Mobile API Secret Path.',
                'usage' => 'Mobile-api Fireblocks client when path is not set.',
                'required' => false,
            ],
            'mobile_api_secret_path' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Mobile API Secret Path',
                'hint' => 'Path to private key file for the mobile Fireblocks API user.',
                'usage' => 'cib-mobile-api FireblocksConfigService / shared resolution via portal config export.',
                'doc_url' => self::doc('api'),
            ],
            'api_key' => [
                'value' => 'xxxx-production-api-key',
                'type' => 'text',
                'label' => 'API Key (legacy)',
                'hint' => 'Deprecated combined key field; use Admin/Mobile API keys instead if present.',
                'usage' => 'Fallback only when split admin/mobile keys are empty.',
                'required' => false,
            ],
            'fire_blocks_secret_key_path' => [
                'value' => '/var/www/etc',
                'type' => 'text',
                'label' => 'Fire Blocks Secret Key Path (legacy)',
                'hint' => 'Legacy secret path; superseded by admin_api_secret_path / mobile_api_secret_path.',
                'usage' => 'Fallback path resolution in FireblocksConfigService.',
                'required' => false,
            ],
            'source_vault_account_id' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Source Vault Account ID',
                'hint' => 'Default operational vault for treasury and withdrawal flows where a single source vault is required.',
                'usage' => 'Withdraw and treasury flows that reference a configured source vault; asset operations that need a default vault id.',
                'doc_url' => self::doc('vaults'),
            ],
            'omnibus_vault_id' => [
                'value' => '0',
                'type' => 'text',
                'label' => 'Omnibus Vault ID',
                'hint' => 'Fireblocks vault account whose balances are fetched for Omnibus Balances and the Transaction Monitoring Omnibus tab. Default 0. Empty is treated as 0.',
                'usage' => 'WithdrawalPoolBalanceSyncService getAccount() for omnibus balance sync. Does not change withdrawal source vault routing.',
                'sample' => '0',
                'doc_url' => self::doc('vaults'),
            ],
            'wallet_pool_id' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Wallet Pool ID',
                'hint' => 'Fireblocks Embedded Wallet / wallet-pool identifier when using pool-based client wallets.',
                'usage' => 'Client wallet and withdraw paths that resolve vault accounts via wallet pool instead of per-asset vault_account_id on assets.',
                'required' => false,
            ],
            'webhook_verify_signature' => [
                'value' => '1',
                'type' => 'checkbox',
                'label' => 'Webhook Verify Signature',
                'hint' => 'When on, incoming Fireblocks webhooks must pass RSA signature verification before processing.',
                'usage' => 'Fireblocks webhook controller / WebhookService — rejects invalid signatures when enabled.',
                'doc_url' => self::doc('webhooks'),
            ],
            'webhook_public_key_type' => [
                'value' => 'production',
                'type' => 'select',
                'label' => 'Webhook Public Key Type',
                'options' => [
                    'sandbox' => 'Sandbox',
                    'production' => 'Production',
                    'eu' => 'EU',
                ],
                'hint' => 'Which Fireblocks-published webhook public key to use for signature verification.',
                'usage' => 'Webhook signature verification key selection when custom key is empty.',
                'doc_url' => self::doc('webhooks'),
            ],
            'webhook_custom_public_key' => [
                'value' => '',
                'type' => 'textarea',
                'label' => 'Webhook Custom Public Key (optional)',
                'hint' => 'PEM public key override when not using Fireblocks default keys.',
                'usage' => 'Webhook verification when set; takes precedence over webhook_public_key_type.',
                'required' => false,
                'doc_url' => self::doc('webhooks'),
            ],
            'travel_rules_enabled' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Travel Rule Enabled',
                'hint' => 'Enables Travel Rule payload handling for supported outbound transfers.',
                'usage' => 'FireblocksConfigService::isTravelRuleEnabled() — withdrawal / compliance flows that attach travel rule data.',
                'doc_url' => self::doc('travel'),
            ],
            'default_fee_level' => [
                'value' => 'MEDIUM',
                'type' => 'select',
                'label' => 'Default Fee Level',
                'options' => [
                    'LOW' => 'Low',
                    'MEDIUM' => 'Medium',
                    'HIGH' => 'High',
                ],
                'hint' => 'Default Fireblocks feeLevel for new transactions when not overridden per request.',
                'usage' => 'Portal/mobile withdraw and transfer builders; monitoring boost default display.',
                'doc_url' => self::doc('fees'),
            ],
            'vault_hidden_on_ui' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Hide New Vault Accounts on Fireblocks UI (hiddenOnUI)',
                'hint' => 'Sets hiddenOnUI when creating client deposit vaults in Fireblocks.',
                'usage' => 'Deposit address / vault creation services — Fireblocks create vault API payload.',
                'doc_url' => self::doc('vaults'),
            ],
            'vault_auto_fuel' => [
                'value' => '1',
                'type' => 'checkbox',
                'label' => 'Auto Fuel New Vault Accounts (autoFuel)',
                'hint' => 'Enables Fireblocks autoFuel on new vault accounts for gas on supported networks.',
                'usage' => 'Vault creation when provisioning client deposit vaults.',
                'doc_url' => self::doc('vaults'),
            ],
            'show_vaults_menu' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Show Fireblocks Vaults Menu Item',
                'hint' => 'Shows Crypto Management → Fireblocks vault browser in the admin menu.',
                'usage' => 'Navigation / menu registration for Fireblocks vault UI routes.',
            ],
            'whitelist_address_api' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Whitelist Address API Key',
                'hint' => 'Optional separate API user for external wallet whitelist / beneficiary address registration.',
                'usage' => 'BeneficiaryService and whitelist address API calls (may differ from main API user permissions).',
                'required' => false,
            ],
            'whitelist_address_api_secret_path' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Whitelist Address API Secret Path',
                'hint' => 'Private key path for the whitelist API user.',
                'usage' => 'BeneficiaryService → whitelist Fireblocks client authentication.',
                'required' => false,
            ],
            'enable_for_clients' => [
                'value' => 'all',
                'type' => 'select',
                'label' => 'Enable Fireblocks for clients',
                'options' => [
                    'all' => 'All',
                    'specific' => 'Specific client IDs',
                ],
                'hint' => 'Restrict which portal clients may use Fireblocks crypto features.',
                'usage' => 'FireblocksConfigService::isEnabledForClient() — client portal and mobile feature gates.',
            ],
            'enable_for_client_ids' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Client IDs',
                'placeholder' => 'e.g. 12, 34, 56',
                'required' => false,
                'show_when' => [
                    'field' => 'enable_for_clients',
                    'value' => 'specific',
                ],
                'hint' => 'Comma-separated client.id values allowed when mode is Specific.',
                'usage' => 'FireblocksConfigService client allow-list check.',
            ],
        ];
    }

    public static function mergeInto(array $existing): array
    {
        $definitions = self::all();
        $merged = [];

        foreach ($definitions as $key => $definition) {
            if (isset($existing[$key])) {
                $merged[$key] = array_merge($definition, [
                    'value' => $existing[$key]['value'] ?? $definition['value'],
                ]);
            } else {
                $merged[$key] = $definition;
            }
        }

        foreach ($existing as $key => $field) {
            if (!isset($merged[$key])) {
                $merged[$key] = $field;
            }
        }

        return $merged;
    }
}
