<?php

namespace App\Modules\Chainalysis\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\CryptoTransactionLog;
use App\Modules\Chainalysis\Models\CryptoAddressCompliance;
use App\Modules\Fireblocks\Models\CryptoBeneficiary;
use App\Modules\FireblocksTransactionsMonitoring\Services\ChainalysisComplianceSyncService;
use App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringConfigService;

class ChainalysisService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $config = ChainAnalysisConfigService::get();

        $this->baseUrl = $config['base_url'];
        $this->apiKey  = $config['api_key'];
    }

    private function logResponse(?int $beneficiaryId, array $details): void
    {
        try {
            CryptoTransactionLog::create([
                'transaction_id' => (string) $beneficiaryId,
                'crypto_system'  => 'chainalysis',
                'source'         => 'client',
                'details'        => $details,
            ]);
        } catch (\Throwable $e) {
            Log::warning('ChainalysisService: failed to write transaction log. ' . $e->getMessage());
        }
    }

    /**
     * Screen a wallet address against Chainalysis KYT.
     * One compliance record per beneficiary — updates on re-scan.
     *
     * @param string   $walletAddress
     * @param string   $entityType      'deposit_address' or 'withdraw_address'
     * @param int|null $beneficiaryId   crypto_beneficiaries.id (null for deposit addresses)
     */
    public function screenAddress(
        string $walletAddress,
        string $entityType,
        ?int $beneficiaryId = null,
        ?string $fireblocksTxId = null,
        ?string $monitoringFlow = null,
    ): CryptoAddressCompliance {
        $response = Http::withHeaders([
            'Token'  => $this->apiKey,
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/entities/{$walletAddress}");
       
        if ($response->failed()) {
            Log::error('ChainalysisService: API call failed', [
                'wallet_address' => $walletAddress,
                'status'         => $response->status(),
                'body'           => $response->body(),
            ]);

            $failedPayload = [
                'action'       => 'screenAddress',
                'wallet'       => $walletAddress,
                'entity_type'  => $entityType,
                'status'       => 'FAILED',
                'http_status'  => $response->status(),
                'error'        => $response->body(),
            ];

            $this->logResponse($beneficiaryId, $failedPayload);

            // Upsert compliance record with failed status
            $compliance = CryptoAddressCompliance::updateOrCreate(
                ['crypto_beneficiary_id' => $beneficiaryId, 'wallet_address' => $walletAddress],
                [
                    'entity_type'   => $entityType,
                    'risk'          => null,
                    'status'        => 'FAILED',
                    'provider'      => 'chainalysis',
                    'complete_info' => $failedPayload,
                ]
            );

            $this->pushAddressScreeningToMonitoring(
                $fireblocksTxId,
                $walletAddress,
                $failedPayload,
                $monitoringFlow ?? 'whitelist_beneficiary',
                'chainalysis_api',
                $beneficiaryId,
            );

            // Throw so the caller can handle the failure
            throw new \RuntimeException('Chainalysis API call failed with HTTP ' . $response->status());
        }

        $data = $response->json();

        Log::info('ChainalysisService: screening complete', [
            'wallet_address' => $walletAddress,
            'risk'           => $data['risk'] ?? null,
            'status'         => $data['status'] ?? null,
        ]);

        $this->logResponse($beneficiaryId, array_merge(['action' => 'screenAddress'], $data));

        // One record per beneficiary — update if already exists
        $compliance = CryptoAddressCompliance::updateOrCreate(
            ['crypto_beneficiary_id' => $beneficiaryId, 'wallet_address' => $walletAddress],
            [
                'entity_type'      => $entityType,
                'risk'             => $data['risk'] ?? null,
                'status'           => $data['status'] ?? null,
                'address_type'     => $data['addressType'] ?? null,
                'cluster_name'     => $data['cluster']['name'] ?? null,
                'cluster_category' => $data['cluster']['category'] ?? null,
                'risk_reason'      => $data['riskReason'] ?? null,
                'provider'         => 'chainalysis',
                'complete_info'    => $data,
            ]
        );

        $this->pushAddressScreeningToMonitoring(
            $fireblocksTxId,
            $walletAddress,
            array_merge($data, ['entity_type' => $entityType]),
            $monitoringFlow ?? 'whitelist_beneficiary',
            'chainalysis_api',
            $beneficiaryId,
        );

        return $compliance;
    }

    public static function attachComplianceToBeneficiary(string $walletAddress, int $beneficiaryId): void
    {
        CryptoAddressCompliance::query()
            ->where('wallet_address', $walletAddress)
            ->where(function ($query) use ($beneficiaryId) {
                $query->whereNull('crypto_beneficiary_id')
                    ->orWhere('crypto_beneficiary_id', $beneficiaryId);
            })
            ->update(['crypto_beneficiary_id' => $beneficiaryId]);
    }

    private function pushAddressScreeningToMonitoring(
        ?string $fireblocksTxId,
        string $walletAddress,
        array $payload,
        string $flow,
        string $triggerSource,
        ?int $beneficiaryId,
    ): void {
        if ($fireblocksTxId === null || trim($fireblocksTxId) === '') {
            return;
        }

        try {
            if (!MonitoringConfigService::isActive()) {
                return;
            }

            app(ChainalysisComplianceSyncService::class)->applyAddressScreeningForTransaction(
                $fireblocksTxId,
                $walletAddress,
                $payload,
                $flow,
                $triggerSource,
                $beneficiaryId,
            );
        } catch (\Throwable $e) {
            Log::warning('ChainalysisService: failed to write transaction monitoring log. ' . $e->getMessage());
        }
    }

    /**
     * Screen a beneficiary's wallet address.
     * Convenience wrapper used from BeneficiaryService / WithdrawController.
     */
    public function screenBeneficiary(CryptoBeneficiary $beneficiary): CryptoAddressCompliance
    {
        return $this->screenAddress(
            $beneficiary->wallet_address,
            'withdraw_address',
            $beneficiary->id
        );
    }
}
