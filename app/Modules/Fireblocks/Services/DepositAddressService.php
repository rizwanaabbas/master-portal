<?php

namespace App\Modules\Fireblocks\Services;

use App\Models\CryptoAddress;
use Fireblocks\Sdk\Models\CreateVaultAccountRequest;
use App\Modules\Fireblocks\Models\Network;
use App\Modules\Fireblocks\Models\Asset;
use App\Modules\FireblocksVaultSweeping\Services\SweepConfigService;
use Fireblocks\Sdk\Facades\Fireblocks;
use Illuminate\Support\Facades\Log;

class DepositAddressService
{
    /**
     * List deposit addresses for a vault account asset directly from Fireblocks SDK.
     *
     * @param string $vaultAccountId
     * @param string $assetId
     * @return array
     * @throws \Exception
     */
    public function listDepositAddressesFromSdk(string $vaultAccountId, string $assetId): array
    {
        try {
            $addresses = Fireblocks::vaults()->listDepositAddresses($vaultAccountId, $assetId);

            return [
                'success' => true,
                'data' => array_map(function ($address) {
                    return [
                        'assetId' => $address->assetId,
                        'address' => $address->address,
                        'description' => $address->description,
                        'tag' => $address->tag,
                        'type' => $address->type,
                        'customerRefId' => $address->customerRefId,
                        'addressFormat' => $address->addressFormat,
                        'legacyAddress' => $address->legacyAddress,
                        'enterpriseAddress' => $address->enterpriseAddress,
                    ];
                }, $addresses)
            ];
        } catch (\Exception $e) {
            Log::error('Fireblocks list deposit addresses failed', [
                'vaultAccountId' => $vaultAccountId,
                'assetId' => $assetId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Create a new deposit address via Fireblocks SDK.
     *
     * @param string $vaultAccountId
     * @param string $assetId
     * @param string|null $description
     * @param string|null $customerRefId
     * @param int|null $accountId Optional local account_id to link the address to
     * @return array
     * @throws \Exception
     */
    public function createDepositAddressViaSdk(
        string $vaultAccountId,
        string $assetId,
        ?string $description = null,
        ?string $customerRefId = null,
        ?int $accountId = null,
        ?int $network_id = null
    ): array {
        try {
           // dd($network_id);
           // dd($vaultAccountId, $assetId, $description, $customerRefId, $accountId);
            $depositAddress = Fireblocks::vaults()->createDepositAddress(
                $vaultAccountId,
                $assetId,
                $description,
                $customerRefId
            );
             $network = Network::where('id', $network_id)->first();
             $asset = Asset::where('id', $assetId)->first();
             Log::info('Created deposit address via SDK', [
                'vaultAccountId' => $vaultAccountId,
                'assetId' => $assetId,
                'description' => $description,
                'customerRefId' => $customerRefId,
                'accountId' => $accountId,
                'network_id' => $network_id,
                'network_name' => $network ? $network->name : null,
                'asset_name' => $asset ? $asset->name : null,
            ]);
            // Persist in the existing crypto_addresses table
            $record = CryptoAddress::create([
                'account_id' => $accountId,
                'vault_account_id' => $vaultAccountId,
                'asset_id' => $assetId,
                'network' => $network ? $network->name : 'NA',
                'crypto_address' => $depositAddress->address,
                'legacy_address' => $depositAddress->legacyAddress ?? null,
                'address_alias' => $depositAddress->description ?? $description,
                'destination_tag' => $depositAddress->tag,
                'ged_external_id' => $depositAddress->customerRefId ?? $customerRefId,
                'provider' => 'fireblocks',
                'network_id' => $network_id,
            ]);
            //dd($record);
            return [
                'success' => true,
                'data' => [
                    'id' => $record->id,
                    'accountId' => $accountId,
                    'vaultAccountId' => $vaultAccountId,
                    'assetId' => $assetId,
                    'address' => $depositAddress->address,
                    'description' => $depositAddress->description ? $depositAddress->description : $description ,
                    'tag' => $depositAddress->tag,
                    'type' => $depositAddress->type,
                    'customerRefId' => $depositAddress->customerRefId,
                    'addressFormat' => $depositAddress->addressFormat,
                    'legacyAddress' => $depositAddress->legacyAddress,
                    'enterpriseAddress' => $depositAddress->enterpriseAddress,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Fireblocks create deposit address failed', [
                'vaultAccountId' => $vaultAccountId,
                'assetId' => $assetId,
                'description' => $description,
                'customerRefId' => $customerRefId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * List locally persisted deposit addresses from crypto_addresses table.
     *
     * @param array $filters
     * @return array
     */
    public function listLocalDepositAddresses(array $filters = []): array
    {
        $query = CryptoAddress::query();

        if (!empty($filters['vault_account_id'])) {
            $query->where('vault_account_id', $filters['vault_account_id']);
        }

        if (!empty($filters['asset_id'])) {
            $query->where('asset_id', $filters['asset_id']);
        }

        if (!empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        if (!empty($filters['address'])) {
            $query->where('crypto_address', 'like', '%' . $filters['address'] . '%');
        }

        return [
            'success' => true,
            'data' => $query->orderBy('created_at', 'desc')->get()
        ];
    }

    /**
     * Get a locally persisted deposit address by ID.
     *
     * @param int $id
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getLocalDepositAddress(int $id): array
    {
        $record = CryptoAddress::findOrFail($id);

        return [
            'success' => true,
            'data' => $record
        ];
    }

    /**
     * List supported assets (currency networks) from Fireblocks SDK.
     *
     * @return array
     * @throws \Exception
     */
    public function listSupportedAssets(): array
    {
        try {
            $assets = Fireblocks::network()->getSupportedAssets();

            return [
                'success' => true,
                'data' => $assets
            ];
        } catch (\Exception $e) {
            Log::error('Fireblocks list supported assets failed', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Create a new vault account via Fireblocks SDK.
     *
     * @param string $name
     * @param bool|null $hiddenOnUI
     * @param string|null $customerRefId
     * @param bool|null $autoFuel
     * @return array
     * @throws \Exception
     */
    public function createVaultAccount(
        string $name,
        ?bool $hiddenOnUI = null,
        ?string $customerRefId = null,
        ?bool $autoFuel = null
    ): array {
        try {
            $request = new CreateVaultAccountRequest($name);

            if ($hiddenOnUI !== null) {
                $request->withHiddenOnUI($hiddenOnUI);
            }

            if ($customerRefId !== null) {
                $request->withCustomerRefId($customerRefId);
            }

            if ($autoFuel !== null) {
                $request->withAutoFuel($autoFuel);
            }

            $vaultAccount = Fireblocks::vaults()->createAccount($request);

            $this->attachClientIntermediaryTag((string) $vaultAccount->id);

            return [
                'success' => true,
                'data' => [
                    'id' => $vaultAccount->id,
                    'name' => $vaultAccount->name,
                    'type' => $vaultAccount->type,
                    'hiddenOnUi' => $vaultAccount->hiddenOnUi,
                    'assets' => $vaultAccount->assets,
                    'customerRefId' => $vaultAccount->customerRefId,
                    'autoFuel' => $vaultAccount->autoFuel,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Fireblocks create vault account failed', [
                'name' => $name,
                'customerRefId' => $customerRefId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Attach the Client Intermediary standard tag — provisioning fails if this throws.
     */
    public function attachClientIntermediaryTag(string $vaultAccountId): void
    {
        $tagId = trim((string) (SweepConfigService::tryGet()['client_intermediary_tag_id'] ?? ''));
        if ($tagId === '') {
            throw new \RuntimeException(
                'Client Intermediary vault tag ID is not configured in Fireblocks Vault Sweeping module settings.'
            );
        }

        try {
            Fireblocks::vaults()->attachOrDetachTags([$vaultAccountId], [$tagId], []);
        } catch (\Throwable $e) {
            if (stripos($e->getMessage(), 'ATTACHMENT_ALREADY_EXISTS') === false) {
                throw $e;
            }
        }

        Log::info('Client Intermediary vault tag attached', [
            'vault_account_id' => $vaultAccountId,
            'tag_id' => $tagId,
        ]);
    }

    public function getVaultAsset($vaultAccountId, $assetId) {
         $depositAddresses = Fireblocks::vaults()->getAssetAddress($vaultAccountId, $assetId);
        return $depositAddresses;
         // dd($depositAddresses);
    }

    /**
     * Create a new vault asset (wallet) inside a vault account via Fireblocks SDK.
     * Maps to POST /v1/vault/accounts/{vaultAccountId}/{assetId}
     *
     * @param string $vaultAccountId
     * @param string $assetId
     * @param string|null $eosAccountName
     * @return array
     * @throws \Exception
     */
    public function createVaultAsset(
        string $vaultAccountId,
        string $assetId,
        ?string $eosAccountName = null, ?string $customerRefId = null, $network_id = null
    ): array {
        try {

            //echo $network_id; die();
            $vaultAsset = Fireblocks::vaults()->createAsset($vaultAccountId, $assetId, $eosAccountName);
            // Fetch deposit addresses for the newly created asset
           // dd($vaultAsset);
            $depositAddresses = Fireblocks::vaults()->getAssetAddress($vaultAccountId, $assetId);
            //dd($depositAddresses);
            $savedAddresses = [];

             $network = Network::where('id', $network_id)->first();
             //$asset = Asset::where('id', $assetId)->first();
            foreach ($depositAddresses as $depositAddress) {
               
                $record = CryptoAddress::create([
                    'vault_account_id' => $vaultAccountId,
                    'asset_id' => $assetId,
                     'account_id' => $customerRefId,
                    'network' =>  $network->name,
                    'network_id' => $network_id,
                    'crypto_address' => $depositAddress['address'],
                    'provider' => 'fireblocks',
                    'legacy_address' => $depositAddress['legacyAddress'] ?? null,
                    'address_alias' =>$depositAddress['description'] ?   $depositAddress['description'] :  $eosAccountName,
                    'destination_tag' => $depositAddress['tag'] ?? null,
                    'ged_external_id' => $customerRefId ?? null,
                ]);

                $savedAddresses[] = [
                    'id' => $record->id,
                    'assetId' => $depositAddress['assetId'],
                    'address' => $depositAddress['address'],
                    'tag' => $depositAddress['tag'] ?? null,
                    'description' => $depositAddress['description'] ?   $depositAddress['description'] :  $eosAccountName,
                    'type' => $depositAddress['type'] ?? null,
                    'legacyAddress' => $depositAddress['legacyAddress'] ?? null,
                    'enterpriseAddress' => $depositAddress['enterpriseAddress'] ?? null,
                ];
            }
            //dd( $savedAddresses);
            $address =  $savedAddresses[0];
             return [
                'success' => true,
                'data' => [
                    'id' => $record->id,
                    'accountId' => null,
                    'vaultAccountId' => $vaultAccountId,
                    'assetId' => $assetId,
                    'address' => $address['address'],
                    'description' => $address['description'] ? $address['description'] : $eosAccountName ,
                    'tag' => $address['tag'],
                    'type' => $address['type'],
                    'customerRefId' => $customerRefId,
                    'addressFormat' => $address['addressFormat'] ?? null,
                    'legacyAddress' => $address['legacyAddress'] ?? null,
                    'enterpriseAddress' => $address['enterpriseAddress'] ?? null,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Fireblocks create vault asset failed', [
                'vaultAccountId' => $vaultAccountId,
                'assetId' => $assetId,
                'eosAccountName' => $eosAccountName,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
