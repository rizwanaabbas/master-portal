<?php

namespace App\Modules\Fireblocks\Services;

use App\Models\Account;
use App\Models\CryptoAddress;
use App\Models\Currency;
use App\Modules\Fireblocks\Models\Asset;
use App\Modules\Fireblocks\Models\FireblocksVaultAccount;
use App\Modules\Fireblocks\Models\Network;
use Fireblocks\Sdk\Facades\Fireblocks;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class VaultDepositAddressSyncService
{
    public function __construct(
        protected DepositAddressService $depositAddressService
    ) {
    }

    /**
     * Sync all supported vault assets after a vault_account.asset.added webhook.
     * Fireblocks may add multiple assets (e.g. USDT/USDC variants); sync every
     * asset present on the vault that is enabled in the portal.
     */
    public function syncFromVaultAssetAdded(array $data): array
    {
        $vaultAccountId = $this->extractVaultAccountId($data);
        if ($vaultAccountId === '') {
            return $this->skipped('Missing vault account id in vault asset webhook');
        }

        $context = $this->enrichContextFromFireblocksVault(
            $vaultAccountId,
            $this->normalizeContext($data)
        );

        return $this->syncVaultDepositAddresses($vaultAccountId, $context);
    }

    /**
     * Sync all supported vault assets for the vault referenced in a transaction webhook.
     */
    public function syncFromTransaction(array $data): array
    {
        $vaultAccountId = $this->extractVaultAccountIdFromTransaction($data);
        if ($vaultAccountId === '') {
            return $this->skipped('No vault account found in transaction webhook');
        }

        $context = $this->normalizeTransactionContext($data);
        $assetId = $this->extractFireblocksAssetId($data);
        $destinationAddress = trim((string) ($context['destinationAddress'] ?? ''));

        if ($destinationAddress === '') {
            return $this->skipped('No destination address on transaction webhook — deferring address sync', [
                'vault_account_id' => $vaultAccountId,
                'asset_id' => $assetId !== '' ? $assetId : null,
            ]);
        }

        if ($assetId !== '') {
            return $this->syncVaultAssetDepositAddresses($vaultAccountId, $assetId, $context);
        }

        return $this->skipped('Transaction webhook missing asset id for deposit address sync', [
            'vault_account_id' => $vaultAccountId,
        ]);
    }

    /**
     * Sync deposit addresses for one supported asset on a vault account.
     */
    public function syncVaultAssetDepositAddresses(
        string $vaultAccountId,
        string $fireblocksAssetId,
        array $context = []
    ): array {
        $vaultAccountId = trim($vaultAccountId);
        $fireblocksAssetId = trim($fireblocksAssetId);

        if ($vaultAccountId === '' || $fireblocksAssetId === '') {
            return $this->skipped('Vault account id or asset id is empty');
        }

        $vault = $this->resolveVaultOwner($vaultAccountId, $context);
        if (!$vault) {
            return $this->skipped('Vault account owner could not be resolved', [
                'vault_account_id' => $vaultAccountId,
                'asset_id' => $fireblocksAssetId,
            ]);
        }

        $asset = $this->resolveConfiguredAsset($fireblocksAssetId);
        if (!$asset) {
            return $this->skipped('Fireblocks asset is not configured for an active currency', [
                'vault_account_id' => $vaultAccountId,
                'asset_id' => $fireblocksAssetId,
            ]);
        }

        $summary = [
            'vault_account_id' => $vaultAccountId,
            'client_id' => $vault->client_id,
            'asset_id' => $fireblocksAssetId,
            'assets_checked' => 1,
            'assets_synced' => 0,
            'addresses_created' => 0,
            'errors' => [],
        ];

        try {
            $created = $this->syncAssetDepositAddresses(
                $vaultAccountId,
                $fireblocksAssetId,
                $asset,
                $vault,
                $context
            );

            $summary['assets_synced'] = 1;
            $summary['addresses_created'] = $created;
        } catch (\Throwable $e) {
            $summary['errors'][] = [
                'asset_id' => $fireblocksAssetId,
                'message' => $e->getMessage(),
            ];

            Log::warning('Fireblocks vault asset deposit address sync failed', [
                'vault_account_id' => $vaultAccountId,
                'client_id' => $vault->client_id,
                'asset_id' => $fireblocksAssetId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to sync deposit address for vault asset',
                'summary' => $summary,
            ];
        }

        return [
            'success' => true,
            'message' => $summary['addresses_created'] > 0
                ? 'Vault deposit address synced for added asset'
                : 'Vault deposit address already registered for added asset',
            'summary' => $summary,
        ];
    }

    /**
     * Fetch all assets on a Fireblocks vault account and sync deposit addresses
     * for assets that are configured and enabled locally.
     */
    public function syncVaultDepositAddresses(string $vaultAccountId, array $context = []): array
    {
        $vaultAccountId = trim($vaultAccountId);
        if ($vaultAccountId === '') {
            return $this->skipped('Vault account id is empty');
        }

        $context = $this->enrichContextFromFireblocksVault($vaultAccountId, $context);
        $vaultAssetIds = $this->fetchVaultAssetIds($vaultAccountId);
        if ($vaultAssetIds->isEmpty()) {
            return $this->skipped('Vault account has no assets in Fireblocks', [
                'vault_account_id' => $vaultAccountId,
            ]);
        }

        $vault = $this->resolveVaultOwner($vaultAccountId, $context);
        $summary = [
            'vault_account_id' => $vaultAccountId,
            'client_id' => $vault?->client_id,
            'assets_checked' => 0,
            'assets_synced' => 0,
            'addresses_created' => 0,
            'skipped_assets' => [],
            'errors' => [],
        ];

        foreach ($vaultAssetIds as $fireblocksAssetId) {
            $summary['assets_checked']++;

            $asset = $this->resolveConfiguredAsset($fireblocksAssetId);
            if (!$asset) {
                $summary['skipped_assets'][] = $fireblocksAssetId;
                continue;
            }

            if (!$vault) {
                $summary['skipped_assets'][] = $fireblocksAssetId;
                continue;
            }

            try {
                $created = $this->syncAssetDepositAddresses(
                    $vaultAccountId,
                    $fireblocksAssetId,
                    $asset,
                    $vault,
                    $context
                );

                $summary['assets_synced']++;
                $summary['addresses_created'] += $created;
            } catch (\Throwable $e) {
                $summary['errors'][] = [
                    'asset_id' => $fireblocksAssetId,
                    'message' => $e->getMessage(),
                ];

                Log::warning('Fireblocks vault asset deposit address sync failed', [
                    'vault_account_id' => $vaultAccountId,
                    'asset_id' => $fireblocksAssetId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($summary['assets_synced'] === 0 && !empty($summary['errors'])) {
            return [
                'success' => false,
                'message' => 'Failed to sync deposit addresses for configured vault assets',
                'summary' => $summary,
            ];
        }

        return [
            'success' => true,
            'message' => $summary['addresses_created'] > 0
                ? 'Vault deposit addresses synced'
                : 'Vault deposit addresses already up to date',
            'summary' => $summary,
        ];
    }

    protected function fetchVaultAssetIds(string $vaultAccountId): Collection
    {
        try {
            $vaultAccount = Fireblocks::vaults()->getAccount($vaultAccountId);
        } catch (\Throwable $e) {
            Log::warning('Fireblocks vault account lookup failed', [
                'vault_account_id' => $vaultAccountId,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }

        return collect($vaultAccount->assets ?? [])
            ->map(function ($asset) {
                if (is_array($asset)) {
                    return trim((string) ($asset['id'] ?? ''));
                }

                if (is_object($asset)) {
                    return trim((string) ($asset->id ?? ''));
                }

                return '';
            })
            ->filter()
            ->unique()
            ->values();
    }

    protected function syncAssetDepositAddresses(
        string $vaultAccountId,
        string $fireblocksAssetId,
        Asset $asset,
        ?FireblocksVaultAccount $vault,
        array $context
    ): int {
        $result = $this->depositAddressService->listDepositAddressesFromSdk($vaultAccountId, $fireblocksAssetId);
        $addresses = $result['data'] ?? [];

        if (!is_array($addresses) || $addresses === []) {
            return 0;
        }

        $network = Network::query()->find($asset->network_id);
        $networkName = $network?->name ?? 'NA';
        $created = 0;
        $skippedUnresolved = 0;
        $targetAddress = strtolower(trim((string) (
            $context['destinationAddress']
            ?? $context['destination_address']
            ?? ''
        )));
        $targetTag = trim((string) ($context['destinationTag'] ?? $context['destination_tag'] ?? ''));

        foreach ($addresses as $addressPayload) {
            if (!is_array($addressPayload)) {
                continue;
            }

            $address = trim((string) ($addressPayload['address'] ?? ''));
            if ($address === '') {
                continue;
            }

            if ($targetAddress !== '') {
                $legacy = strtolower(trim((string) ($addressPayload['legacyAddress'] ?? '')));
                $addressLower = strtolower($address);
                if ($addressLower !== $targetAddress && ($legacy === '' || $legacy !== $targetAddress)) {
                    continue;
                }
            }

            $tag = trim((string) ($addressPayload['tag'] ?? ''));
            if ($targetTag !== '' && $tag !== $targetTag) {
                continue;
            }

            $account = $this->resolveClientAccountForAddress($vault, $asset, $addressPayload, $context);
            if (!$account) {
                $skippedUnresolved++;
                continue;
            }

            if ($this->addressAlreadyRegistered($fireblocksAssetId, $address, $tag)) {
                continue;
            }

            CryptoAddress::create([
                'account_id' => $account->id,
                'vault_account_id' => $vaultAccountId,
                'asset_id' => $fireblocksAssetId,
                'network_id' => $asset->network_id,
                'network' => $networkName,
                'crypto_address' => $address,
                'legacy_address' => $addressPayload['legacyAddress'] ?? null,
                'address_alias' => $addressPayload['description'] ?? null,
                'destination_tag' => $tag !== '' ? $tag : null,
                'ged_external_id' => $addressPayload['customerRefId'] ?? (string) $account->id,
                'provider' => 'fireblocks',
            ]);

            $created++;
        }

        if ($skippedUnresolved > 0) {
            Log::debug('Fireblocks deposit address sync skipped addresses without resolvable account', [
                'vault_account_id' => $vaultAccountId,
                'asset_id' => $fireblocksAssetId,
                'skipped_count' => $skippedUnresolved,
                'scoped_to_deposit_address' => $targetAddress !== '',
            ]);
        }

        return $created;
    }

    protected function addressAlreadyRegistered(string $fireblocksAssetId, string $address, string $tag): bool
    {
        $query = CryptoAddress::query()
            ->where('provider', 'fireblocks')
            ->where('asset_id', $fireblocksAssetId)
            ->where(function ($addressQuery) use ($address) {
                $addressQuery->where('crypto_address', $address)
                    ->orWhere('legacy_address', $address);
            });

        if ($tag !== '') {
            $query->where('destination_tag', $tag);
        } else {
            $query->where(function ($tagQuery) {
                $tagQuery->whereNull('destination_tag')
                    ->orWhere('destination_tag', '');
            });
        }

        return $query->exists();
    }

    protected function resolveConfiguredAsset(string $fireblocksAssetId): ?Asset
    {
        $asset = Asset::query()
            ->where('asset_id', $fireblocksAssetId)
            ->enabled()
            ->first();

        if (!$asset) {
            return null;
        }

        $currency = Currency::query()
            ->where('id', $asset->currency_id)
            ->where('is_crypto', true)
            ->where('is_active', true)
            ->first();

        if (!$currency) {
            return null;
        }

        $expectsTestnet = !app()->environment('production');
        if ($expectsTestnet && !$asset->is_testnet) {
            return null;
        }

        if (!$expectsTestnet && $asset->is_testnet) {
            return null;
        }

        return $asset;
    }

    protected function resolveVaultOwner(string $vaultAccountId, array $context): ?FireblocksVaultAccount
    {
        $vault = FireblocksVaultAccount::query()
            ->where('vault_id', $vaultAccountId)
            ->first();

        if ($vault) {
            return $vault;
        }

        $clientId = $this->inferClientIdFromContext($context);
        if (!$clientId) {
            return null;
        }

        // Note: To avoid duplicate Integrity constraint violation fireblocks_vault_accounts.fireblocks_vault_accounts_account_id_unique on Staging/Local
        if (env('APP_ENV') === "local" || env('APP_ENV') === "staging") {
            $existingClientVault = FireblocksVaultAccount::query()
                // ->where('vault_id', $vaultAccountId)
                ->where('client_id', $clientId)
                ->first();
    
            if ($existingClientVault) {
                return $existingClientVault;
            }
        }

        return FireblocksVaultAccount::query()->firstOrCreate(
            ['vault_id' => $vaultAccountId],
            [
                'client_id' => $clientId,
                'vault_title' => $context['accountName']
                    ?? $context['account_name']
                    ?? ('client-vault-' . $clientId),
            ]
        );
    }

    protected function inferClientIdFromContext(array $context): ?int
    {
        foreach (['customerRefId', 'customer_ref_id'] as $key) {
            if (empty($context[$key]) || !ctype_digit((string) $context[$key])) {
                continue;
            }

            $value = (int) $context[$key];
            $account = Account::withoutGlobalScopes()->find($value);
            if ($account?->client_id) {
                return (int) $account->client_id;
            }

            if (Account::withoutGlobalScopes()->where('client_id', $value)->exists()) {
                return $value;
            }
        }

        $accountName = (string) ($context['accountName'] ?? $context['account_name'] ?? '');
        if ($accountName !== '' && preg_match('/client-vault-(\d+)/i', $accountName, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function resolveClientAccountForAddress(
        ?FireblocksVaultAccount $vault,
        Asset $asset,
        array $addressPayload,
        array $context
    ): ?Account {
        $customerRefId = trim((string) ($addressPayload['customerRefId'] ?? ''));
        if ($customerRefId !== '' && ctype_digit($customerRefId)) {
            $account = Account::withoutGlobalScopes()
                ->where('id', (int) $customerRefId)
                ->where('account_currency', $asset->currency_id)
                ->where('is_active', true)
                ->when($vault, fn ($query) => $query->where('client_id', $vault->client_id))
                ->first();

            if ($account) {
                return $account;
            }
        }

        if (!$vault) {
            return null;
        }

        return $this->resolveClientAccount($vault, $asset, $context);
    }

    protected function resolveClientAccount(FireblocksVaultAccount $vault, Asset $asset, array $context): ?Account
    {
        foreach (['customerRefId', 'customer_ref_id'] as $key) {
            if (empty($context[$key]) || !ctype_digit((string) $context[$key])) {
                continue;
            }

            $candidate = Account::withoutGlobalScopes()
                ->where('id', (int) $context[$key])
                ->where('client_id', $vault->client_id)
                ->where('account_currency', $asset->currency_id)
                ->where('is_active', true)
                ->first();

            if ($candidate) {
                return $candidate;
            }
        }

        $existingAddressAccountId = CryptoAddress::query()
            ->where('network_id', $asset->network_id)
            ->where('provider', 'fireblocks')
            ->whereIn('account_id', Account::withoutGlobalScopes()
                ->where('client_id', $vault->client_id)
                ->where('account_currency', $asset->currency_id)
                ->pluck('id'))
            ->value('account_id');

        if ($existingAddressAccountId) {
            return Account::withoutGlobalScopes()->find($existingAddressAccountId);
        }

        return Account::withoutGlobalScopes()
            ->where('client_id', $vault->client_id)
            ->where('account_currency', $asset->currency_id)
            ->where('is_active', true)
            ->whereHas('currency', function ($query) {
                $query->where('is_crypto', true)->where('is_active', true);
            })
            ->orderBy('id')
            ->first();
    }

    protected function enrichContextFromFireblocksVault(string $vaultAccountId, array $context): array
    {
        if (!empty($context['customerRefId']) && !empty($context['accountName'])) {
            return $context;
        }

        try {
            $vaultAccount = Fireblocks::vaults()->getAccount($vaultAccountId);
            $context['accountName'] = $context['accountName']
                ?? $context['account_name']
                ?? ($vaultAccount->name ?? null);
            $context['customerRefId'] = $context['customerRefId']
                ?? $context['customer_ref_id']
                ?? ($vaultAccount->customerRefId ?? null);
        } catch (\Throwable $e) {
            Log::warning('Fireblocks vault account context lookup failed', [
                'vault_account_id' => $vaultAccountId,
                'error' => $e->getMessage(),
            ]);
        }

        return $context;
    }

    protected function extractFireblocksAssetId(array $data): string
    {
        return trim((string) ($data['assetId'] ?? $data['asset_id'] ?? ''));
    }

    protected function extractVaultAccountId(array $data): string
    {
        return trim((string) (
            $data['accountId']
            ?? $data['account_id']
            ?? $data['vaultAccountId']
            ?? $data['vault_account_id']
            ?? ''
        ));
    }

    protected function extractVaultAccountIdFromTransaction(array $data): string
    {
        $destinationType = strtoupper((string) ($data['destination']['type'] ?? ''));
        if ($destinationType === 'VAULT_ACCOUNT') {
            $vaultAccountId = $this->normalizeVaultAccountId($data['destination']['id'] ?? null);
            if ($vaultAccountId !== '') {
                return $vaultAccountId;
            }
        }

        foreach (['destination', 'source'] as $leg) {
            if (strtoupper((string) ($data[$leg]['type'] ?? '')) !== 'VAULT_ACCOUNT') {
                continue;
            }

            $vaultAccountId = $this->normalizeVaultAccountId($data[$leg]['id'] ?? null);
            if ($vaultAccountId !== '') {
                return $vaultAccountId;
            }
        }

        return '';
    }

    protected function normalizeVaultAccountId(mixed $vaultAccountId): string
    {
        $vaultAccountId = trim((string) $vaultAccountId);
        if ($vaultAccountId === '' || $vaultAccountId === '0') {
            return '';
        }

        return $vaultAccountId;
    }

    protected function normalizeContext(array $data): array
    {
        return array_merge($data, [
            'accountName' => $data['accountName'] ?? $data['account_name'] ?? null,
            'customerRefId' => $data['customerRefId']
                ?? $data['customer_ref_id']
                ?? ($data['customerRefId'] ?? null),
        ]);
    }

    protected function normalizeTransactionContext(array $data): array
    {
        return array_merge($data, [
            'customerRefId' => $data['customerRefId']
                ?? $data['customer_ref_id']
                ?? ($data['destination']['customerRefId'] ?? null),
            'destinationTag' => $data['destinationTag'] ?? $data['destination_tag'] ?? null,
            'destinationAddress' => $data['destinationAddress'] ?? $data['destination_address'] ?? null,
            'accountName' => $data['destination']['name']
                ?? $data['accountName']
                ?? $data['account_name']
                ?? null,
        ]);
    }

    protected function skipped(string $message, array $context = []): array
    {
        Log::info('Fireblocks vault deposit address sync skipped', array_merge(['message' => $message], $context));

        return [
            'success' => true,
            'skipped' => true,
            'message' => $message,
        ];
    }
}
