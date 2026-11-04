<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Helpers\Common;
use App\Models\FireblocksTransaction;
use App\Modules\Chainalysis\Models\CryptoAddressCompliance;
use App\Modules\Chainalysis\Services\ChainAnalysisConfigService;
use App\Modules\Chainalysis\Services\ChainalysisService;
use Illuminate\Support\Facades\Log;

class ChainalysisComplianceSyncService
{
    public function __construct(
        protected TransactionMonitoringSync $monitoringSync,
    ) {
    }

    /**
     * Persist Fireblocks AML / Chainalysis compliance from webhook or poll payload.
     */
    public function apply(FireblocksTransaction $record, array $payload, string $triggerSource = 'webhook'): void
    {
        $compliance = $this->extractComplianceResults($payload);
        if ($compliance === null) {
            return;
        }

        $normalized = $this->normalizeStatus($compliance);
        $screenAddress = $this->resolveScreeningAddress($payload, $record);
        $links = $this->buildScanLinks($screenAddress, $record->fireblocks_tx_id, $compliance);

        $record->chainalysis_status = $normalized;
        $record->chainalysis_scan_url = $links['primary'] ?? null;

        $responseData = is_array($record->response_data) ? $record->response_data : [];
        $responseData['complianceResults'] = $compliance;
        $responseData['chainalysis_scan_links'] = $links;
        $record->response_data = $responseData;

        $requestPayload = [
            'complianceResults' => $compliance,
            'fireblocks_tx_id' => $record->fireblocks_tx_id,
            'asset_id' => $payload['assetId'] ?? $record->asset_id,
            'screening_address' => $screenAddress,
        ];

        $responsePayload = [
            'normalized_status' => $normalized,
            'scan_links' => $links,
            'complianceResults' => $compliance,
        ];

        Log::info('[Fireblocks transaction monitoring] Chainalysis compliance', [
            'fireblocks_tx_id' => $record->fireblocks_tx_id,
            'trigger' => $triggerSource,
            'chainalysis_status' => $normalized,
            'request_payload' => $requestPayload,
            'response_payload' => $responsePayload,
        ]);

        $this->monitoringSync->log(
            $record->fireblocks_tx_id,
            'chainalysis_compliance',
            $triggerSource,
            null,
            null,
            null,
            null,
            $this->isSuccessStatus($normalized),
            $requestPayload,
            $responsePayload,
            $this->isFailureStatus($normalized) ? $normalized : null,
        );
    }

    /**
     * Log Chainalysis address screening (beneficiary whitelist / withdraw destination) on a Fireblocks tx.
     */
    public function applyAddressScreeningForTransaction(
        string $fireblocksTxId,
        string $walletAddress,
        array $screeningPayload,
        string $flow,
        string $triggerSource = 'client',
        ?int $beneficiaryId = null,
    ): void {
        if (!Common::isModuleEnabled('FireblocksTransactionsMonitoring') || trim($fireblocksTxId) === '') {
            return;
        }

        $record = FireblocksTransaction::query()->where('fireblocks_tx_id', $fireblocksTxId)->first();
        if (!$record) {
            return;
        }

        $walletAddress = trim($walletAddress);
        $normalized = $this->normalizeAddressScreenStatus($screeningPayload);
        $links = $this->buildScanLinks($walletAddress, $fireblocksTxId, $screeningPayload);

        $responseData = is_array($record->response_data) ? $record->response_data : [];
        $responseData['chainalysis_address_screen'] = [
            'flow' => $flow,
            'wallet_address' => $walletAddress,
            'beneficiary_id' => $beneficiaryId,
            'normalized_status' => $normalized,
            'screening' => $screeningPayload,
            'scan_links' => $links,
            'recorded_at' => now()->toIso8601String(),
        ];
        $record->response_data = $responseData;

        if (empty($responseData['complianceResults'])) {
            $record->chainalysis_status = $normalized;
            $record->chainalysis_scan_url = $links['primary'] ?? null;
        }

        $record->save();

        $requestPayload = [
            'flow' => $flow,
            'wallet_address' => $walletAddress,
            'beneficiary_id' => $beneficiaryId,
            'fireblocks_tx_id' => $fireblocksTxId,
            'screening_request' => [
                'entity_type' => $screeningPayload['entity_type'] ?? 'withdraw_address',
            ],
        ];

        $responsePayload = [
            'normalized_status' => $normalized,
            'scan_links' => $links,
            'screening' => $screeningPayload,
        ];

        Log::info('[Fireblocks transaction monitoring] Chainalysis address screen', [
            'fireblocks_tx_id' => $fireblocksTxId,
            'flow' => $flow,
            'trigger' => $triggerSource,
            'chainalysis_status' => $normalized,
            'request_payload' => $requestPayload,
            'response_payload' => $responsePayload,
        ]);

        $this->monitoringSync->log(
            $fireblocksTxId,
            'chainalysis_address_screen',
            $triggerSource,
            null,
            null,
            null,
            null,
            $this->isSuccessStatus($normalized),
            $requestPayload,
            $responsePayload,
            $this->isFailureStatus($normalized) ? $normalized : null,
            'chainalysis_address:' . $flow . ':' . $fireblocksTxId,
        );
    }

    /**
     * When Fireblocks compliance is missing, call Chainalysis Entities API for the tx wallet and persist on the record.
     */
    public function refreshChainalysisIfMissing(FireblocksTransaction $record, string $triggerSource = 'manual'): bool
    {
        $resolved = $this->resolveForGrid($record);
        if (!empty($resolved['status'])) {
            return false;
        }

        $address = $this->resolveScreeningAddress([], $record);
        if ($address !== '' && strtolower((string) ($record->direction ?? '')) !== 'incoming') {
            $this->applyStoredBeneficiaryScreeningForWithdraw(
                $record->fireblocks_tx_id,
                $address,
            );
            $record = $record->fresh() ?? $record;
            $resolved = $this->resolveForGrid($record);
            if (!empty($resolved['status'])) {
                return true;
            }
        }
        if ($address === '') {
            return false;
        }

        try {
            if (!ChainAnalysisConfigService::isSanctionsScreeningEnabled()) {
                return false;
            }
        } catch (\Throwable) {
            return false;
        }

        $entityType = strtolower((string) ($record->direction ?? '')) === 'incoming'
            ? 'deposit_address'
            : 'withdraw_address';

        try {
            $compliance = app(ChainalysisService::class)->screenAddress(
                $address,
                $entityType,
                null,
                $record->fireblocks_tx_id,
                'manual_sync',
            );

            $payload = is_array($compliance->complete_info) ? $compliance->complete_info : [];
            $payload['risk'] = $compliance->risk ?? ($payload['risk'] ?? null);
            $payload['status'] = $compliance->status ?? ($payload['status'] ?? null);
            $payload['entity_type'] = $compliance->entity_type ?? $entityType;
            $payload['risk_reason'] = $compliance->risk_reason ?? ($payload['riskReason'] ?? null);
            $payload['screened_at'] = $compliance->updated_at?->toIso8601String();

            $this->applyAddressScreeningForTransaction(
                $record->fireblocks_tx_id,
                $address,
                $payload,
                'manual_sync',
                $triggerSource,
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('[Fireblocks transaction monitoring] Chainalysis manual refresh failed', [
                'fireblocks_tx_id' => $record->fireblocks_tx_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function applyStoredBeneficiaryScreeningForWithdraw(
        string $fireblocksTxId,
        string $walletAddress,
        ?int $beneficiaryId = null,
    ): void {
        $walletAddress = trim($walletAddress);
        if ($walletAddress === '') {
            return;
        }

        $compliance = CryptoAddressCompliance::query()
            ->when($beneficiaryId, fn ($q) => $q->where('crypto_beneficiary_id', $beneficiaryId))
            ->where('wallet_address', $walletAddress)
            ->orderByDesc('updated_at')
            ->first();

        if (!$compliance) {
            $compliance = CryptoAddressCompliance::query()
                ->where('wallet_address', $walletAddress)
                ->orderByDesc('updated_at')
                ->first();
        }

        if (!$compliance) {
            return;
        }

        $payload = is_array($compliance->complete_info) ? $compliance->complete_info : [];
        $payload['risk'] = $compliance->risk ?? ($payload['risk'] ?? null);
        $payload['status'] = $compliance->status ?? ($payload['status'] ?? null);
        $payload['entity_type'] = $compliance->entity_type ?? 'withdraw_address';
        $payload['risk_reason'] = $compliance->risk_reason ?? ($payload['riskReason'] ?? null);
        $payload['screened_at'] = $compliance->updated_at?->toIso8601String();

        $this->applyAddressScreeningForTransaction(
            $fireblocksTxId,
            $walletAddress,
            $payload,
            'whitelist_beneficiary',
            'withdraw',
            $beneficiaryId ?? $compliance->crypto_beneficiary_id,
        );
    }

    public function normalizeAddressScreenStatus(array $screening): string
    {
        if (strtoupper(trim((string) ($screening['status'] ?? ''))) === 'FAILED'
            || !empty($screening['error'])
            || !empty($screening['http_status'])) {
            return 'FAILED';
        }

        $fromApi = $this->normalizeStatus($screening);
        if ($fromApi !== 'UNKNOWN') {
            return $fromApi;
        }

        $risk = strtolower(trim((string) ($screening['risk'] ?? '')));
        if ($risk !== '') {
            try {
                if (ChainAnalysisConfigService::isEnabled() && ChainAnalysisConfigService::isRisky($risk)) {
                    return 'REJECTED';
                }
            } catch (\Throwable) {
                if (in_array($risk, ['high', 'severe', 'medium'], true)) {
                    return 'REJECTED';
                }
            }

            return 'PASSED';
        }

        return 'UNKNOWN';
    }

    /**
     * @return array<string, string|null>
     */
    public function buildScanLinks(string $address, string $fireblocksTxId, array $compliance): array
    {
        $links = [
            'primary' => null,
            'address_entities_api' => null,
            'kyt_portal' => null,
        ];

        if ($address !== '') {
            $links['address_entities_api'] = $this->publicEntitiesUrl($address);
            $entityPortal = $this->kytEntityPortalUrl($address);
            if ($entityPortal !== null) {
                $links['kyt_portal'] = $entityPortal;
                $links['primary'] = $entityPortal;
            }
        }

        $externalRef = trim((string) (
            $compliance['externalId']
            ?? $compliance['external_id']
            ?? $compliance['providerReference']
            ?? $compliance['screeningId']
            ?? ''
        ));

        $kytBase = rtrim((string) config('fireblocks-monitoring.chainalysis_kyt_portal_url', 'https://kyt.chainalysis.com'), '/');
        if ($kytBase !== '' && $externalRef !== '') {
            $links['kyt_transaction'] = $kytBase . '/transactions/' . rawurlencode($externalRef);
            if ($links['primary'] === null) {
                $links['primary'] = $links['kyt_transaction'];
            }
        } elseif ($kytBase !== '' && $links['kyt_portal'] === null) {
            $links['kyt_portal'] = $kytBase;
            if ($links['primary'] === null) {
                $links['primary'] = $kytBase;
            }
        }

        if ($links['primary'] === null && $fireblocksTxId !== '') {
            $consoleBase = rtrim((string) config('fireblocks-monitoring.fireblocks_console_tx_url', ''), '/');
            if ($consoleBase !== '') {
                $links['primary'] = $consoleBase . '/' . rawurlencode($fireblocksTxId);
            }
        }

        return $links;
    }

    public function normalizeStatus(array $compliance): string
    {
        $candidates = [
            $compliance['status'] ?? null,
            $compliance['screeningStatus'] ?? null,
            $compliance['result'] ?? null,
            $compliance['decision'] ?? null,
        ];

        foreach ($candidates as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $this->canonicalizeStatus($value);
            }
        }

        if (!empty($compliance['failed']) || !empty($compliance['isFailed'])) {
            return 'FAILED';
        }

        return 'UNKNOWN';
    }

    /**
     * Fireblocks AML can prefix direction (IncomingCompleted / INCOMING_COMPLETED).
     * Ops should see the screening outcome only (Completed).
     */
    public function canonicalizeStatus(string $status): string
    {
        $status = strtoupper(trim($status));
        $status = str_replace([' ', '-'], '_', $status);
        $status = preg_replace('/^(INCOMING|OUTGOING)_+/', '', $status) ?? $status;

        foreach (['INCOMING', 'OUTGOING'] as $direction) {
            if ($status !== $direction && str_starts_with($status, $direction)) {
                $status = ltrim(substr($status, strlen($direction)), '_');
                break;
            }
        }

        return $status !== '' ? $status : 'UNKNOWN';
    }

    public function formatStatusLabel(string $status): string
    {
        $canonical = $this->canonicalizeStatus($status);
        $words = strtolower(str_replace('_', ' ', $canonical));

        return $words === '' ? '—' : ucwords($words);
    }

    /**
     * Status + scan URL for monitoring grid (DB columns with response_data fallback).
     *
     * @return array{status: ?string, scan_url: ?string}
     */
    public function resolveForGrid(object $row): array
    {
        $status = trim((string) ($row->chainalysis_status ?? ''));
        $scanUrl = trim((string) ($row->chainalysis_scan_url ?? ''));

        $responseData = $row->response_data ?? null;
        if (is_string($responseData)) {
            $decoded = json_decode($responseData, true);
            $responseData = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($responseData)) {
            $responseData = [];
        }

        $compliance = $responseData['complianceResults'] ?? null;
        if ($status === '' && is_array($compliance) && $compliance !== []) {
            $status = $this->normalizeStatus($compliance);
        }

        $addressScreen = $responseData['chainalysis_address_screen'] ?? null;
        if ($status === '' && is_array($addressScreen) && !empty($addressScreen['normalized_status'])) {
            $status = (string) $addressScreen['normalized_status'];
        }

        $scanLinks = is_array($responseData['chainalysis_scan_links'] ?? null)
            ? $responseData['chainalysis_scan_links']
            : [];

        if ($scanUrl === '' && !empty($scanLinks['kyt_portal'])) {
            $scanUrl = trim((string) $scanLinks['kyt_portal']);
        }

        if ($scanUrl === '' && !empty($scanLinks['primary'])) {
            $scanUrl = trim((string) $scanLinks['primary']);
        }

        if ($scanUrl === '' && is_array($addressScreen) && !empty($addressScreen['scan_links']['kyt_portal'])) {
            $scanUrl = trim((string) $addressScreen['scan_links']['kyt_portal']);
        }

        if ($scanUrl === '' && is_array($addressScreen) && !empty($addressScreen['scan_links']['primary'])) {
            $scanUrl = trim((string) $addressScreen['scan_links']['primary']);
        }

        $scanUrl = $this->resolveKytEntityLinkForRow($row, $scanUrl);

        if ($status !== '') {
            $status = $this->canonicalizeStatus($status);
        }

        if ($status === '' || $status === 'UNKNOWN') {
            return ['status' => null, 'scan_url' => $scanUrl !== '' ? $scanUrl : null];
        }

        return ['status' => $status, 'scan_url' => $scanUrl !== '' ? $scanUrl : null];
    }

    public function gridBadgeHtml(object $row): string
    {
        $resolved = $this->resolveForGrid($row);
        $status = $resolved['status'];
        if ($status === null || $status === '') {
            return '<span class="text-muted">—</span>';
        }

        $badge = $this->statusBadge($status);
        $html = '<span class="badge ' . e($badge['class']) . '" title="Chainalysis / AML">' . e($badge['label']) . '</span>';

        $scanUrl = $resolved['scan_url'];
        if ($scanUrl !== null && $scanUrl !== '') {
            $html .= ' <a href="' . e($scanUrl) . '" target="_blank" rel="noopener noreferrer" class="btn btn-xs btn-outline-secondary" title="Open Chainalysis KYT entity"><i class="fal fa-external-link"></i></a>';
        }

        return $html;
    }

    public function statusBadge(?string $status): array
    {
        $status = $this->canonicalizeStatus((string) $status);
        if ($status === '' || $status === 'UNKNOWN') {
            return ['class' => $status === '' ? 'badge-secondary' : 'badge fb-ca-unknown', 'label' => $status === '' ? '—' : 'Unknown'];
        }

        $label = $this->formatStatusLabel($status);

        if ($this->isSuccessStatus($status)) {
            return ['class' => 'badge fb-ca-passed', 'label' => $label];
        }

        if ($this->isFailureStatus($status)) {
            return ['class' => 'badge fb-ca-rejected', 'label' => $label];
        }

        if (str_contains($status, 'PENDING') || str_contains($status, 'SCREEN') || str_contains($status, 'PROGRESS')) {
            return ['class' => 'badge fb-ca-pending', 'label' => $label];
        }

        return ['class' => 'badge fb-ca-unknown', 'label' => $label];
    }

    public function kytEntityPortalUrl(string $address): ?string
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        $kytBase = rtrim((string) config('fireblocks-monitoring.chainalysis_kyt_portal_url', 'https://kyt.chainalysis.com'), '/');
        if ($kytBase === '') {
            return null;
        }

        return $kytBase . '/entities/' . rawurlencode($address);
    }

    /**
     * Same KYT entity preference as transaction detail: use /entities/{address} when we know the wallet.
     */
    protected function resolveKytEntityLinkForRow(object $row, string $scanUrl): string
    {
        $destination = trim((string) ($row->destination_address ?? ''));
        $entityPortal = $this->kytEntityPortalUrl($destination);
        if ($entityPortal !== null) {
            return $entityPortal;
        }

        $screenAddress = $this->resolveScreeningAddressFromRow($row);
        $entityPortal = $this->kytEntityPortalUrl($screenAddress);
        if ($entityPortal !== null) {
            return $entityPortal;
        }

        return $this->preferKytEntityPortalUrl($scanUrl, $screenAddress);
    }

    protected function preferKytEntityPortalUrl(string $scanUrl, string $address): string
    {
        $entityPortal = $this->kytEntityPortalUrl($address);
        if ($entityPortal === null) {
            return $scanUrl;
        }

        if ($scanUrl === ''
            || str_contains($scanUrl, 'public.chainalysis.com')
            || str_contains($scanUrl, '/api/v1/entities/')
            || (str_contains($scanUrl, 'kyt.chainalysis.com') && !str_contains($scanUrl, '/entities/'))) {
            return $entityPortal;
        }

        return $scanUrl;
    }

    protected function resolveScreeningAddressFromRow(object $row): string
    {
        foreach ([
            $row->destination_address ?? null,
            $row->source_address ?? null,
        ] as $value) {
            $value = trim((string) $value);
            if ($value !== '' && !$this->looksLikeVaultId($value)) {
                return $value;
            }
        }

        $responseData = $row->response_data ?? null;
        if (is_string($responseData)) {
            $responseData = json_decode($responseData, true);
        }
        if (is_array($responseData)) {
            return $this->resolveScreeningAddress($responseData, $row instanceof FireblocksTransaction ? $row : new FireblocksTransaction((array) $row));
        }

        return '';
    }

    public function isSuccessStatus(string $status): bool
    {
        $status = $this->canonicalizeStatus($status);

        return in_array($status, [
            'COMPLETED',
            'COMPLETE',
            'SUCCESS',
            'SUCCEEDED',
            'PASSED',
            'PASS',
            'APPROVED',
            'ACCEPTED',
            'CLEAR',
        ], true);
    }

    public function isFailureStatus(string $status): bool
    {
        $status = $this->canonicalizeStatus($status);

        return in_array($status, [
            'FAILED',
            'FAIL',
            'FAILURE',
            'REJECTED',
            'REJECT',
            'DECLINED',
            'BLOCKED',
            'DENIED',
        ], true)
            || str_contains($status, 'REJECT')
            || str_contains($status, 'FAIL');
    }

    protected function extractComplianceResults(array $payload): ?array
    {
        $candidates = [
            $payload['complianceResults'] ?? null,
            $payload['amlScreeningResult'] ?? null,
            $payload['amlScreening'] ?? null,
            $payload['compliance'] ?? null,
        ];

        foreach ($candidates as $compliance) {
            if (!is_array($compliance) || $compliance === []) {
                continue;
            }

            if (isset($compliance['complianceResults']) && is_array($compliance['complianceResults'])) {
                return $compliance['complianceResults'];
            }

            return $compliance;
        }

        return null;
    }

    protected function resolveScreeningAddress(array $payload, FireblocksTransaction $record): string
    {
        $candidates = [
            $payload['destinationAddress'] ?? null,
            $record->destination_address ?? null,
            $payload['sourceAddress'] ?? null,
        ];

        foreach ($candidates as $value) {
            $value = trim((string) $value);
            if ($value !== '' && !$this->looksLikeVaultId($value)) {
                return $value;
            }
        }

        return '';
    }

    protected function looksLikeVaultId(string $value): bool
    {
        return ctype_digit($value) && strlen($value) <= 8;
    }

    protected function publicEntitiesUrl(string $address): ?string
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        try {
            if (ChainAnalysisConfigService::isEnabled()) {
                $base = ChainAnalysisConfigService::get()['base_url'];

                return rtrim($base, '/') . '/entities/' . rawurlencode($address);
            }
        } catch (\Throwable) {
            // fall through to default public API path
        }

        return 'https://public.chainalysis.com/api/v1/entities/' . rawurlencode($address);
    }
}
