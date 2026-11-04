<?php

namespace App\Modules\Fireblocks\Services;

use Fireblocks\Sdk\Api\FireblocksClient;
use App\Models\CryptoTransactionLog;
use App\Modules\Fireblocks\Models\CryptoBeneficiary;
use App\Modules\Fireblocks\Models\CryptoExternalWallet;
use Illuminate\Support\Facades\Log;

class BeneficiaryService
{
    // Asset types that use additionalInfo instead of (or alongside) a plain address
    const FIAT_ASSET_TYPES = ['iban', 'aba', 'sepa', 'ach', 'us_wire', 'spei', 'pix', 'momo', 'lbt', 'interac', 'payid', 'chaps'];

    private const OPERATION_UNAVAILABLE_MESSAGE = 'This operation is not available at the moment. Please contact support.';

    private function fbLog(string $action, $transactionId, array $response): void
    {
        try {
            CryptoTransactionLog::create([
                'transaction_id' => (string) $transactionId,
                'crypto_system'  => 'Fireblocks',
                'source'         => 'client',
                'details'        => array_merge(['action' => $action], $response),
            ]);
        } catch (\Throwable $e) {
            Log::warning('BeneficiaryService fbLog failed: ' . $e->getMessage());
        }
    }

    private function operationUnavailableResponse(): array
    {
        return [
            'success' => false,
            'error'   => 'operation_unavailable',
            'message' => self::OPERATION_UNAVAILABLE_MESSAGE,
        ];
    }

    private function whitelistWallets()
    {
        return app(FireblocksClient::class)->forWhitelistAddress()->wallets();
    }

    /**
     * Step 1 — Find-or-create the external wallet container on Fireblocks for this client.
     * One wallet per client. Returns the local CryptoExternalWallet record.
     *
     * $data keys: client_id, name (client_name - client_id), customer_ref_id, created_by
     */
    public function findOrCreateExternalWallet(array $data): array
    {
        try {
            // Return existing if already created for this client
            $existing = CryptoExternalWallet::where('client_id', $data['client_id'])->first();
            if ($existing) {
                return ['success' => true, 'data' => $existing, 'created' => false];
            }

            if (!FireblocksConfigService::hasWhitelistCredentials()) {
                return $this->operationUnavailableResponse();
            }

            try {
                $fbWallet = $this->whitelistWallets()->createExternalWallet($data['name'], $data['customer_ref_id']);
                $this->fbLog('createExternalWallet', $data['client_id'], $fbWallet);
            } catch (\Throwable $e) {
                // Fireblocks rejects duplicate names — find and reuse
                if (str_contains($e->getMessage(), 'already exists')) {
                    $allWallets = $this->whitelistWallets()->listExternalWallets();
                    $fbWallet   = collect($allWallets)->firstWhere('name', $data['name']);

                    if (!$fbWallet) {
                        throw $e;
                    }

                    $this->fbLog('reuseExternalWallet', $data['client_id'], $fbWallet);
                } else {
                    throw $e;
                }
            }

            $wallet = CryptoExternalWallet::create([
                'client_id'            => $data['client_id'],
                'fireblocks_wallet_id' => $fbWallet['id'],
                'name'                 => $data['name'],
                'customer_ref_id'      => $data['customer_ref_id'],
                'created_by'           => $data['created_by'],
            ]);

            return ['success' => true, 'data' => $wallet, 'created' => true];

        } catch (\Throwable $e) {
            Log::error('BeneficiaryService@findOrCreateExternalWallet: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Step 2 — Add a whitelisted address (asset) to the external wallet on Fireblocks
     * and create the local CryptoBeneficiary record.
     *
     * $data keys:
     *   external_wallet_id, account_id, label, currency_id, network_id,
     *   asset_id (Fireblocks asset string e.g. "BTC"), asset_type,
     *   wallet_address, destination_tag (optional),
     *   additional_info (optional array, required for fiat rails), created_by
     */
    public function addWalletAsset(CryptoExternalWallet $externalWallet, array $data): array
    {
        try {
            if (!FireblocksConfigService::hasWhitelistCredentials()) {
                return $this->operationUnavailableResponse();
            }

            $additionalInfo = $data['additional_info'] ?? null;

            // Fireblocks' address/tag schema requires `tag` to be a string (or omitted);
            // sending null fails the oneOf validation, so coerce empty tags to ''.
            $tag = trim((string) ($data['destination_tag'] ?? ''));

            $fbAsset = $this->whitelistWallets()->addExternalWalletAsset(
                $externalWallet->fireblocks_wallet_id,
                $data['asset_id'],
                $data['wallet_address'],
                $tag
            );
            $this->fbLog('addExternalWalletAsset', $data['account_id'], $fbAsset);

            $beneficiary = CryptoBeneficiary::create([
                'external_wallet_id'         => $externalWallet->id,
                'fireblocks_wallet_id'       => $externalWallet->fireblocks_wallet_id,
                'account_id'                 => $data['account_id'],
                'label'                      => $data['label'],
                'wallet_address'             => $data['wallet_address'],
                'destination_tag'            => $data['destination_tag'] ?? null,
                'currency_id'               => $data['currency_id'],
                'network_id'                => $data['network_id'],
                'is_active'                  => true,
                'provider'                   => 'Fireblocks',
                'asset_type'                 => $data['asset_type'] ?? 'crypto',
                'additional_info'            => $additionalInfo,
                'created_by'                 => $data['created_by'],
                'fireblocks_asset_id'        => $data['asset_id'],
                'fireblocks_asset_status'    => $fbAsset['status'] ?? null,
                'fireblocks_activation_time' => isset($fbAsset['activationTime']) && $fbAsset['activationTime']
                    ? \Carbon\Carbon::parse($fbAsset['activationTime'])
                    : null,
            ]);

            return ['success' => true, 'data' => $beneficiary->load(['currency:id,short,name', 'network:id,code,name', 'compliance'])];

        } catch (\Throwable $e) {
            Log::error('BeneficiaryService@addWalletAsset: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch a single external wallet from Fireblocks and sync beneficiary status.
     */
    public function fetchAndSync(CryptoBeneficiary $beneficiary): array
    {
        try {
            $externalWallet = $beneficiary->externalWallet;

            if (!$externalWallet?->fireblocks_wallet_id) {
                return ['success' => false, 'error' => 'No Fireblocks wallet ID on record.'];
            }

            if (!FireblocksConfigService::hasWhitelistCredentials()) {
                return [
                    'success' => true,
                    'data'    => $beneficiary->load(['currency:id,short,name', 'network:id,code,name', 'externalWallet']),
                ];
            }

            $fbWallet = $this->whitelistWallets()->getExternalWallet($externalWallet->fireblocks_wallet_id);

            // Find the matching asset by fireblocks_asset_id
            $asset = collect($fbWallet['assets'] ?? [])
                ->firstWhere('id', $beneficiary->fireblocks_asset_id)
                ?? collect($fbWallet['assets'] ?? [])->first();

            if ($asset) {
                $beneficiary->update([
                    'fireblocks_asset_status'    => $asset['status'] ?? $beneficiary->fireblocks_asset_status,
                    'fireblocks_activation_time' => isset($asset['activationTime']) && $asset['activationTime']
                        ? \Carbon\Carbon::parse($asset['activationTime'])
                        : $beneficiary->fireblocks_activation_time,
                ]);
            }

            return ['success' => true, 'data' => $beneficiary->fresh(['currency:id,short,name', 'network:id,code,name', 'externalWallet'])];

        } catch (\Throwable $e) {
            Log::error('BeneficiaryService@fetchAndSync: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * List beneficiaries for an account, syncing Fireblocks status from the external wallet.
     */
    public function listForAccount(int $accountId): array
    {
        try {
            $beneficiaries = CryptoBeneficiary::where('account_id', $accountId)
                ->where('is_active', true)
                ->with(['network:id,code,name', 'externalWallet'])
                ->get();

            if (!FireblocksConfigService::hasWhitelistCredentials()) {
                return ['success' => true, 'data' => $beneficiaries];
            }

            // Group by external wallet to batch the Fireblocks API calls
            $walletIds = $beneficiaries
                ->pluck('externalWallet.fireblocks_wallet_id')
                ->filter()
                ->unique()
                ->values();

            $fbAssetsMap = [];
            foreach ($walletIds as $walletId) {
                try {
                    $fbWallet = $this->whitelistWallets()->getExternalWallet($walletId);
                    foreach ($fbWallet['assets'] ?? [] as $asset) {
                        $fbAssetsMap[$walletId][$asset['id']] = $asset;
                    }
                } catch (\Throwable $e) {
                    Log::warning("BeneficiaryService: could not fetch wallet {$walletId}: " . $e->getMessage());
                }
            }

            $beneficiaries->each(function ($b) use ($fbAssetsMap) {
                $walletId = $b->externalWallet?->fireblocks_wallet_id;
                $assetId  = $b->fireblocks_asset_id;

                if ($walletId && $assetId && isset($fbAssetsMap[$walletId][$assetId])) {
                    $asset = $fbAssetsMap[$walletId][$assetId];
                    $b->fireblocks_asset_status    = $asset['status'] ?? $b->fireblocks_asset_status;
                    $b->fireblocks_activation_time = isset($asset['activationTime']) && $asset['activationTime']
                        ? \Carbon\Carbon::parse($asset['activationTime'])
                        : $b->fireblocks_activation_time;
                    $b->saveQuietly();
                }
            });

            return ['success' => true, 'data' => $beneficiaries];

        } catch (\Throwable $e) {
            Log::error('BeneficiaryService@listForAccount: ' . $e->getMessage());
            $beneficiaries = CryptoBeneficiary::where('account_id', $accountId)
                ->where('is_active', true)
                ->with(['currency:id,short,name', 'network:id,code,name'])
                ->get();
            return ['success' => true, 'data' => $beneficiaries];
        }
    }

    /**
     * Soft-delete beneficiary locally (Fireblocks asset removal is optional/admin action).
     */
    public function delete(CryptoBeneficiary $beneficiary): array
    {
        try {
            $beneficiary->update(['is_active' => false]);
            $beneficiary->delete();
            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('BeneficiaryService@delete: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
