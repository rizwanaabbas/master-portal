<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Support\SweepDestination;
use App\Modules\FireblocksVaultSweeping\Support\SweepAssetMetadata;
use App\Modules\FireblocksVaultSweeping\Support\SweepFireblocksDiagnostics;
use App\Modules\FireblocksVaultSweeping\Support\SweepFireblocksTransactions;
use App\Modules\FireblocksVaultSweeping\Support\SweepLocalTransactionRecorder;
use App\Modules\FireblocksVaultSweeping\Support\SweepMath;
use Fireblocks\Sdk\Facades\Fireblocks;
use Fireblocks\Sdk\Models\TransactionRequest;
use Illuminate\Support\Facades\Log;

class TronGasFundingService
{
    public const GAS_BUFFER_MULTIPLIER = '1.15';

    public function __construct(
        protected SweepLogService $logger,
        protected ?SweepBalanceService $balances = null,
        protected ?SweepFireblocksTransactions $fireblocksTx = null,
    ) {
        $this->balances = $balances ?? app(SweepBalanceService::class);
        $this->fireblocksTx = $fireblocksTx ?? app(SweepFireblocksTransactions::class);
    }

    public function usesEvmGasRelay(string $sourceAssetId): bool
    {
        return SweepAssetMetadata::usesEvmGasRelay($sourceAssetId);
    }

    public function requiresTronGasFunding(FireblocksSweepRule $rule, string $sourceAssetId): bool
    {
        return $this->requiresGasFunding($rule, $sourceAssetId);
    }

    public function requiresGasFunding(FireblocksSweepRule $rule, string $sourceAssetId): bool
    {
        $meta = SweepAssetMetadata::describe(null, $sourceAssetId);

        return ($meta['gas_required'] ?? false) && ($meta['type'] ?? '') === 'token';
    }

    public function resolveGasAssetId(string $sourceAssetId): string
    {
        $meta = SweepAssetMetadata::describe(null, $sourceAssetId);

        return (string) ($meta['gas_asset_id'] ?? 'TRX');
    }

    public function resolveFundingVault(FireblocksSweepRule $rule, ?array $config = null, ?string $gasAssetId = null): ?string
    {
        if (!$this->supportsAutomaticGasFeed((string) $gasAssetId)) {
            return null;
        }

        $ruleVault = trim((string) $rule->gas_funding_vault_id);
        if ($ruleVault !== '' && !SweepDestination::isPlaceholderVaultId($ruleVault)) {
            return $ruleVault;
        }

        $config = $config ?? SweepConfigService::tryGet() ?? [];
        $station = trim((string) ($config['default_tron_station_vault_id'] ?? ''));

        return SweepDestination::isPlaceholderVaultId($station) ? null : $station;
    }

    public function supportsAutomaticGasFeed(string $gasAssetId): bool
    {
        $gasAssetId = strtoupper(trim($gasAssetId));

        return in_array($gasAssetId, ['TRX', 'TRX_TEST'], true)
            || str_starts_with($gasAssetId, 'TRX_');
    }

    /**
     * Ensure native gas is available in the source vault before a token sweep.
     *
     * @return array{status: string, job_id?: int, reason?: string, message?: string}
     */
    public function ensureGasFunded(
        FireblocksSweepJob $job,
        FireblocksSweepRule $rule,
        string $sourceVaultId,
        string $sourceAssetId,
        string $sweepAmount,
        string $destinationVaultId,
        string $transferAssetId,
        ?array $config = null,
        bool $dryRun = false
    ): array {
        $config = $config ?? SweepConfigService::tryGet() ?? [];
        $gasAssetId = $this->resolveGasAssetId($sourceAssetId);
        $usesGasRelay = $rule->shouldUseGasless($sourceAssetId);

        if (SweepDestination::isPlaceholderVaultId($destinationVaultId)) {
            return [
                'status' => 'skipped',
                'reason' => 'invalid_destination',
                'job_id' => $job->id,
                'message' => 'Destination omnibus vault is not configured (cannot estimate gas or submit sweep).',
            ];
        }

        $fundingVaultId = $this->resolveFundingVault($rule, $config, $gasAssetId);

        if ($fundingVaultId === $sourceVaultId) {
            return ['status' => 'skipped', 'reason' => 'invalid_gas_funding_vault', 'job_id' => $job->id];
        }

        try {
            $requiredGas = $this->estimateRequiredGas(
                $sourceVaultId,
                $transferAssetId,
                $sweepAmount,
                $destinationVaultId,
                $job,
                $usesGasRelay,
                $rule->feeLevel()
            );
        } catch (\Throwable $e) {
            $estimateTxRequest = $this->buildFeeEstimateRequest(
                $sourceVaultId,
                $transferAssetId,
                $sweepAmount,
                $destinationVaultId,
                $rule->feeLevel()
            );

            $failureContext = SweepFireblocksDiagnostics::executionFailureContext($e, array_merge(
                SweepFireblocksDiagnostics::apiRequestContext('POST', '/v1/transactions/estimate_fee', $estimateTxRequest, $usesGasRelay),
                [
                'source_vault_id' => $sourceVaultId,
                'transfer_asset_id' => $transferAssetId,
                'gas_asset_id' => $gasAssetId,
                'sweep_amount' => $sweepAmount,
                'destination_vault_id' => $destinationVaultId,
                'fireblocks_step' => 'estimate_fee',
                ]
            ));

            $this->logger->logDiagnostic(
                'warning',
                'gas_estimate_failed',
                SweepFireblocksDiagnostics::formatExceptionMessage($e, 'Fireblocks fee estimate failed'),
                $job,
                context: $failureContext
            );

            return [
                'status' => 'skipped',
                'reason' => 'gas_estimate_failed',
                'job_id' => $job->id,
                'message' => SweepFireblocksDiagnostics::formatExceptionMessage($e),
            ];
        }

        $requiredGas = $this->applyTargetGasBalance($requiredGas, $rule);
        $availableGasSnap = $usesGasRelay
            ? ['available' => '0', 'found' => false]
            : $this->balances->getVaultAssetBalance($sourceVaultId, $gasAssetId, true);
        $availableGas = (string) ($availableGasSnap['available'] ?? '0');

        $this->logger->log('gas_fee_estimated', sprintf(
            $usesGasRelay
                ? 'Fireblocks gas-relay fee estimate: ~%s %s (useGasless — source vault %s does not need native gas)'
                : 'Fireblocks fee estimate: ~%s %s required on vault %s (available %s %s)',
            $requiredGas,
            $gasAssetId,
            $sourceVaultId,
            ...($usesGasRelay ? [] : [$availableGas, $gasAssetId])
        ), $job, context: array_merge($this->gasAmountContext($gasAssetId, $availableGas, $requiredGas), [
            'source_asset_id' => $sourceAssetId,
            'transfer_asset_id' => $transferAssetId,
            'destination_vault_id' => $destinationVaultId,
            'fireblocks_step' => 'estimate_fee',
            'endpoint' => 'POST /v1/transactions/estimate_fee',
            'use_gasless' => $usesGasRelay,
            'gas_funding_mode' => $usesGasRelay ? 'gas_relay' : 'native',
        ]));

        $job->update([
            'gas_asset_id' => $gasAssetId,
            'gas_required_amount' => $requiredGas,
            'gas_funding_vault_id' => $usesGasRelay ? null : $fundingVaultId,
            'planned_sweep_amount' => $sweepAmount,
        ]);

        if ($usesGasRelay) {
            $this->logger->log(
                'gas_relay_ready',
                sprintf('ERC-20 sweep will use Fireblocks gas relay (useGasless) — no %s required in source vault %s', $gasAssetId, $sourceVaultId),
                $job,
                context: [
                    'gas_asset_id' => $gasAssetId,
                    'required_gas' => $requiredGas,
                    'use_gasless' => true,
                    'gas_funding_mode' => 'gas_relay',
                ]
            );

            return ['status' => 'gas_sufficient', 'job_id' => $job->id, 'gas_relay' => true];
        }

        if ($availableGasSnap['found'] && SweepMath::comp($availableGas, $requiredGas, 18) >= 0) {
            $this->logger->log(
                'gas_sufficient',
                sprintf('Source vault has enough %s for token sweep', $gasAssetId),
                $job,
                context: $this->gasAmountContext($gasAssetId, $availableGas, $requiredGas)
            );

            return ['status' => 'gas_sufficient', 'job_id' => $job->id];
        }

        $feedAmount = $this->calculateFeedAmount($availableGas, $requiredGas);

        if (SweepMath::comp($feedAmount, '0', 18) <= 0) {
            return ['status' => 'gas_sufficient', 'job_id' => $job->id];
        }

        $job->update(['gas_feed_amount' => $feedAmount]);

        if (!$this->supportsAutomaticGasFeed($gasAssetId)) {
            $gasDiag = SweepFireblocksDiagnostics::insufficientNativeGasDiagnostics(
                $sourceVaultId,
                $gasAssetId,
                $sourceAssetId,
                $availableGas,
                $requiredGas,
                (bool) $availableGasSnap['found'],
                'POST /v1/transactions/estimate_fee'
            );

            $this->logger->logDiagnostic(
                'warning',
                'insufficient_native_gas',
                $gasDiag['message'],
                $job,
                context: array_merge($this->gasAmountContext($gasAssetId, $availableGas, $requiredGas), [
                    'source_asset_id' => $sourceAssetId,
                    'gas_wallet_found' => $availableGasSnap['found'],
                    'failure_reason' => $gasDiag['failure_reason'],
                    'why' => $gasDiag['why'],
                    'diagnosis' => $gasDiag['diagnosis'],
                    'fireblocks_step' => 'preflight_native_gas',
                ])
            );

            return [
                'status' => 'skipped',
                'reason' => $gasDiag['failure_reason'],
                'job_id' => $job->id,
                'message' => $gasDiag['message'],
            ];
        }

        if ($fundingVaultId === null) {
            return [
                'status' => 'skipped',
                'reason' => 'tron_station_not_configured',
                'job_id' => $job->id,
            ];
        }

        $fundingSnap = $this->balances->getVaultAssetBalance($fundingVaultId, $gasAssetId, true);
        if (!$fundingSnap['found']) {
            $message = sprintf(
                'Gas funding vault %s does not have %s enabled. Enable the asset on that vault in Fireblocks or fund source vault %s directly.',
                $fundingVaultId,
                $gasAssetId,
                $sourceVaultId
            );

            $this->logger->logDiagnostic(
                'warning',
                'gas_funding_vault_missing_asset',
                $message,
                $job,
                context: [
                    'funding_vault_id' => $fundingVaultId,
                    'gas_asset_id' => $gasAssetId,
                    'feed_amount' => $feedAmount,
                ]
            );

            return [
                'status' => 'skipped',
                'reason' => 'gas_funding_vault_missing_asset',
                'job_id' => $job->id,
                'message' => $message,
            ];
        }

        if (SweepMath::comp((string) $fundingSnap['available'], $feedAmount, 18) < 0) {
            $message = sprintf(
                'Gas funding vault %s has insufficient %s (need %s, available %s).',
                $fundingVaultId,
                $gasAssetId,
                $feedAmount,
                $fundingSnap['available']
            );

            $this->logger->logDiagnostic('warning', 'gas_funding_vault_insufficient', $message, $job, context: [
                'funding_vault_id' => $fundingVaultId,
                'gas_asset_id' => $gasAssetId,
                'feed_amount' => $feedAmount,
                'available_gas' => $fundingSnap['available'],
            ]);

            return [
                'status' => 'skipped',
                'reason' => 'gas_funding_vault_insufficient',
                'job_id' => $job->id,
                'message' => $message,
            ];
        }

        if ($dryRun) {
            $this->logger->log(
                'gas_feed_dry_run',
                sprintf('Dry run — would fund %s from gas funding vault before sweep', $gasAssetId),
                $job,
                context: array_merge($this->gasAmountContext($gasAssetId, $availableGas, $requiredGas), [
                    'funding_vault_id' => $fundingVaultId,
                    'feed_amount' => $feedAmount,
                ])
            );

            return ['status' => 'gas_sufficient', 'job_id' => $job->id];
        }

        return $this->submitGasFeed($job, $fundingVaultId, $sourceVaultId, $gasAssetId, $feedAmount, $rule->feeLevel());
    }

    /**
     * Unstick jobs marked waiting_for_gas with no Fireblocks gas tx (or a dead gas tx id).
     * Submit gas immediately or advance to token sweep when gas is already on the vault.
     *
     * @return array<string, mixed>
     */
    public function recoverGasLegForJob(FireblocksSweepJob $job): array
    {
        $job->refresh();

        if (trim((string) ($job->fireblocks_tx_id ?? '')) !== '') {
            return ['status' => $job->status, 'job_id' => $job->id, 'recovered' => false, 'reason' => 'token_leg_exists'];
        }

        if (!in_array($job->status, [
            FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
            FireblocksSweepJob::STATUS_GAS_FUNDED,
            FireblocksSweepJob::STATUS_PENDING,
        ], true)) {
            return ['status' => $job->status, 'job_id' => $job->id, 'recovered' => false];
        }

        $rule = FireblocksSweepRule::query()->active()->where('id', $job->rule_id)->first();
        if ($rule === null) {
            return ['status' => $job->status, 'job_id' => $job->id, 'recovered' => false, 'reason' => 'rule_disabled'];
        }

        if (!$this->requiresGasFunding($rule, $job->asset_id)) {
            return ['status' => $job->status, 'job_id' => $job->id, 'recovered' => false, 'reason' => 'gas_not_required'];
        }

        $gasTxId = trim((string) ($job->gas_feed_fireblocks_tx_id ?? ''));
        if ($gasTxId !== '') {
            try {
                $apiTx = Fireblocks::transactions()->get($gasTxId);
                $status = trim((string) ($apiTx->status ?? ''));
                $subStatus = isset($apiTx->subStatus) ? trim((string) $apiTx->subStatus) : null;
                $payload = is_array($apiTx) ? $apiTx : (array) json_decode(json_encode($apiTx), true);

                if ($status !== '') {
                    $normalized = strtoupper($status);
                    $isFailure = in_array($normalized, ['FAILED', 'REJECTED', 'CANCELLED', 'BLOCKED'], true);

                    if (!$isFailure) {
                        $this->handleFeedWebhook($gasTxId, $status, $payload);
                        $job->refresh();

                        return [
                            'status' => $job->status,
                            'job_id' => $job->id,
                            'recovered' => true,
                            'gas_feed_fireblocks_tx_id' => $gasTxId,
                            'still_blocking' => in_array($job->status, FireblocksSweepJob::ACTIVE_FIREBLOCKS_STATUSES, true),
                        ];
                    }

                    Log::warning('[Sweep gas recovery] Clearing failed gas feed tx — will resubmit', [
                        'sweep_job_id' => $job->id,
                        'gas_feed_fireblocks_tx_id' => $gasTxId,
                        'fireblocks_status' => $normalized,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('[Sweep gas recovery] Gas feed tx not found in Fireblocks — will resubmit', [
                    'sweep_job_id' => $job->id,
                    'gas_feed_fireblocks_tx_id' => $gasTxId,
                    'error' => $e->getMessage(),
                ]);
            }

            $job->update([
                'gas_feed_fireblocks_tx_id' => null,
                'gas_feed_external_tx_id' => null,
                'status' => FireblocksSweepJob::STATUS_PENDING,
                'skip_reason' => null,
                'failed_at' => null,
            ]);
            $job->refresh();
        } elseif ($job->status === FireblocksSweepJob::STATUS_WAITING_FOR_GAS) {
            Log::warning('[Sweep gas recovery] Job waiting_for_gas but no gas_feed_fireblocks_tx_id — resubmitting gas feed', [
                'sweep_job_id' => $job->id,
                'source_vault_id' => $job->source_vault_id,
                'asset_id' => $job->asset_id,
            ]);
            $job->update([
                'status' => FireblocksSweepJob::STATUS_PENDING,
                'skip_reason' => null,
                'failed_at' => null,
            ]);
            $job->refresh();
        }

        $config = SweepConfigService::tryGet() ?? [];
        $destinationVaultId = SweepDestination::resolveVaultId($rule, $config)
            ?? trim((string) $job->destination_vault_id);
        $transferAssetId = $rule->targetAssetId() ?? $job->asset_id;
        $sweepAmount = app(SweepExecutionService::class)->resolvePlannedSweepAmountForJob($job, $rule);

        if (SweepMath::comp($sweepAmount, '0', 18) <= 0) {
            return [
                'status' => FireblocksSweepJob::STATUS_SKIPPED,
                'job_id' => $job->id,
                'recovered' => false,
                'reason' => 'insufficient_after_reserve',
            ];
        }

        $gasResult = $this->ensureGasFunded(
            $job,
            $rule,
            $job->source_vault_id,
            $job->asset_id,
            $sweepAmount,
            $destinationVaultId,
            $transferAssetId,
            $config,
            false
        );

        $job->refresh();

        if (($gasResult['status'] ?? '') === 'gas_sufficient') {
            $result = app(SweepExecutionService::class)->resumeAfterGasFeed($job, $rule);
            $job->refresh();

            return array_merge(['recovered' => true, 'job_id' => $job->id], $result);
        }

        return array_merge([
            'recovered' => ($gasResult['status'] ?? '') === FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
            'job_id' => $job->id,
            'still_blocking' => in_array($job->status, FireblocksSweepJob::ACTIVE_FIREBLOCKS_STATUSES, true),
        ], $gasResult);
    }

    public function handleFeedWebhook(string $fireblocksTxId, string $status, array $webhookData = []): bool
    {
        $lifecycleStatus = strtoupper(trim((string) ($webhookData['status'] ?? $status)));
        $normalizedStatus = $lifecycleStatus;
        $subStatus = isset($webhookData['subStatus']) ? (string) $webhookData['subStatus'] : null;
        // Token sweep runs only after Fireblocks lifecycle COMPLETED (TRX landed on vault).
        $isSuccess = $lifecycleStatus === 'COMPLETED';
        $isFailure = in_array($lifecycleStatus, ['FAILED', 'REJECTED', 'CANCELLED', 'BLOCKED'], true);

        if (!$isSuccess && !$isFailure) {
            return false;
        }

        $job = $this->findSweepJobForGasFeedWebhook($fireblocksTxId, $webhookData);

        if (!$job && $isSuccess && self::isGasFeedTransaction($webhookData)) {
            $vaultId = (string) ($webhookData['destination']['id'] ?? '');
            $job = app(SweepJobRecoveryService::class)->findLatestOpenJobForVault($vaultId, $fireblocksTxId);
        }

        if (!$job) {
            if (self::isGasFeedTransaction($webhookData)) {
                Log::info('Fireblocks gas feed webhook — no sweep job matched', [
                    'fireblocks_tx_id' => $fireblocksTxId,
                    'status' => $normalizedStatus,
                    'external_tx_id' => $webhookData['externalTxId'] ?? null,
                    'destination_vault_id' => $webhookData['destination']['id'] ?? null,
                ]);
            }

            return false;
        }

        if ($isSuccess) {
            $now = now();
            $this->logger->log('gas_feed_confirmed', sprintf('%s gas feed completed — submitting token sweep', $job->gas_asset_id ?? 'Native'), $job, context: [
                'gas_feed_fireblocks_tx_id' => $fireblocksTxId,
                'webhook_at' => $now->toDateTimeString(),
                'sweep_job_status_before' => $job->status,
            ]);
            $this->logGasFeedWebhookStatus($job, $normalizedStatus, $fireblocksTxId, $webhookData, $now);

            $job->update([
                'status' => FireblocksSweepJob::STATUS_GAS_FUNDED,
                'fireblocks_status' => $normalizedStatus,
                'fireblocks_sub_status' => $webhookData['subStatus'] ?? null,
                'fireblocks_status_updated_at' => $now,
                'skip_reason' => null,
                'failed_at' => null,
            ]);

            $this->resumeTokenSweepAfterGasFeed($job->fresh());

            return true;
        }

        $now = now();
        $this->logGasFeedWebhookStatus($job, $normalizedStatus, $fireblocksTxId, $webhookData, $now);

        $this->logger->log('gas_feed_failed', sprintf('%s gas feed failed: %s', $job->gas_asset_id ?? 'Native', $normalizedStatus), $job, level: 'error', context: [
            'gas_feed_fireblocks_tx_id' => $fireblocksTxId,
            'webhook_status' => $normalizedStatus,
            'webhook_at' => $now->toDateTimeString(),
        ]);

        $job->update([
            'status' => FireblocksSweepJob::STATUS_FAILED,
            'skip_reason' => 'gas_feed_' . strtolower($normalizedStatus),
            'fireblocks_status' => $normalizedStatus,
            'fireblocks_sub_status' => $webhookData['subStatus'] ?? null,
            'fireblocks_status_updated_at' => $now,
            'failed_at' => $now,
        ]);

        return true;
    }

    public function estimateRequiredGas(
        string $sourceVaultId,
        string $transferAssetId,
        string $sweepAmount,
        string $destinationVaultId,
        ?FireblocksSweepJob $job = null,
        bool $useGasRelay = false,
        string $feeLevel = 'LOW'
    ): string {
        $txRequest = $this->buildFeeEstimateRequest(
            $sourceVaultId,
            $transferAssetId,
            $sweepAmount,
            $destinationVaultId,
            $feeLevel
        );

        $endpoint = '/v1/transactions/estimate_fee';
        $this->logger->logFireblocksRequest(
            'estimate_fee',
            'POST',
            $endpoint,
            $txRequest,
            $job,
            $sourceVaultId,
            $transferAssetId,
            [
                'destination_vault_id' => $destinationVaultId,
                'sweep_amount' => $sweepAmount,
                'fee_level' => $feeLevel,
                'use_gasless' => $useGasRelay,
                'gas_funding_mode' => $useGasRelay ? 'gas_relay' : 'native',
            ],
            $useGasRelay
        );

        $estimate = $this->fireblocksTx->estimateFee($txRequest, $useGasRelay);
        $networkFee = $this->extractNetworkFee($estimate, $feeLevel);

        if (SweepMath::comp($networkFee, '0', 18) <= 0) {
            throw new \RuntimeException('Unable to estimate native network fee for token sweep.');
        }

        return SweepMath::mul($networkFee, self::GAS_BUFFER_MULTIPLIER, 18);
    }

    protected function buildFeeEstimateRequest(
        string $sourceVaultId,
        string $transferAssetId,
        string $sweepAmount,
        string $destinationVaultId,
        string $feeLevel = 'LOW'
    ): TransactionRequest {
        $txRequest = new TransactionRequest();
        $txRequest
            ->withAsset($transferAssetId)
            ->fromVaultAccount($sourceVaultId)
            ->toVaultAccount($destinationVaultId)
            ->withAmount($sweepAmount)
            ->withFeeLevel($this->normalizeFeeLevel($feeLevel));

        return $txRequest;
    }

    protected function applyTargetGasBalance(string $estimatedRequired, FireblocksSweepRule $rule): string
    {
        $target = trim((string) $rule->feed_trx_amount);
        if ($target === '' || SweepMath::comp($target, '0', 18) <= 0) {
            return $estimatedRequired;
        }

        return SweepMath::comp($target, $estimatedRequired, 18) >= 0 ? $target : $estimatedRequired;
    }

    /** @deprecated Use applyTargetGasBalance() */
    protected function applyTargetTrxBalance(string $estimatedRequired, FireblocksSweepRule $rule): string
    {
        return $this->applyTargetGasBalance($estimatedRequired, $rule);
    }

    protected function calculateFeedAmount(string $availableGas, string $requiredGas): string
    {
        if (SweepMath::comp($availableGas, $requiredGas, 18) >= 0) {
            return '0';
        }

        return SweepMath::sub($requiredGas, $availableGas, 18);
    }

    /**
     * @param  array<string, mixed>  $estimate
     */
    protected function extractNetworkFee(array $estimate, string $feeLevel = 'LOW'): string
    {
        $preferred = strtolower($this->normalizeFeeLevel($feeLevel));
        $preferredFee = $estimate[$preferred]['networkFee'] ?? $estimate[$preferred]['network_fee'] ?? null;
        if ($preferredFee !== null && is_numeric($preferredFee) && (float) $preferredFee > 0) {
            return (string) $preferredFee;
        }

        foreach (['low', 'medium', 'high'] as $level) {
            $fee = $estimate[$level]['networkFee'] ?? $estimate[$level]['network_fee'] ?? null;
            if ($fee !== null && is_numeric($fee) && (float) $fee > 0) {
                return (string) $fee;
            }
        }

        $direct = $estimate['networkFee'] ?? $estimate['network_fee'] ?? null;
        if ($direct !== null && is_numeric($direct)) {
            return (string) $direct;
        }

        return '0';
    }

    protected function normalizeFeeLevel(string $feeLevel): string
    {
        $level = strtoupper(trim($feeLevel));

        return in_array($level, FireblocksSweepRule::FEE_LEVELS, true) ? $level : 'LOW';
    }

    protected function submitGasFeed(
        FireblocksSweepJob $job,
        string $fundingVaultId,
        string $sourceVaultId,
        string $gasAssetId,
        string $feedAmount,
        string $feeLevel = 'LOW'
    ): array {
        $externalTxId = $this->buildGasFeedExternalTxId($job->id);

        if (FireblocksSweepJob::query()->where('gas_feed_external_tx_id', $externalTxId)->exists()) {
            $externalTxId = $this->buildGasFeedExternalTxId($job->id);
        }

        $txRequest = new TransactionRequest();
        $txRequest
            ->withAsset($gasAssetId)
            ->fromVaultAccount($fundingVaultId)
            ->toVaultAccount($sourceVaultId)
            ->withAmount($feedAmount)
            ->withFeeLevel($this->normalizeFeeLevel($feeLevel))
            ->withExternalTxId($externalTxId)
            ->withNote(sprintf('Gas feed (%s) for sweep job #%d', $gasAssetId, $job->id));

        $endpoint = '/v1/transactions';
        $this->logger->logFireblocksRequest(
            'create_gas_feed',
            'POST',
            $endpoint,
            $txRequest,
            $job,
            $sourceVaultId,
            $gasAssetId,
            [
                'funding_vault_id' => $fundingVaultId,
                'feed_amount' => $feedAmount,
            ]
        );

        try {
            $transaction = Fireblocks::transactions()->create($txRequest);
        } catch (\Throwable $e) {
            $failureContext = SweepFireblocksDiagnostics::executionFailureContext($e, array_merge(
                SweepFireblocksDiagnostics::apiRequestContext('POST', $endpoint, $txRequest),
                [
                'job_id' => $job->id,
                'funding_vault_id' => $fundingVaultId,
                'source_vault_id' => $sourceVaultId,
                'gas_asset_id' => $gasAssetId,
                'feed_amount' => $feedAmount,
                'fireblocks_step' => 'create_gas_feed',
                ]
            ));

            $this->logger->logDiagnostic(
                'error',
                'gas_feed_submit_failed',
                SweepFireblocksDiagnostics::formatExceptionMessage($e, 'Fireblocks gas feed create failed'),
                $job,
                context: $failureContext
            );

            throw $e;
        }

        $job->update([
            'status' => FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
            'gas_feed_fireblocks_tx_id' => $transaction->id ?? null,
            'gas_feed_external_tx_id' => $externalTxId,
        ]);

        try {
            SweepLocalTransactionRecorder::recordVaultTransfer(
                $transaction,
                $gasAssetId,
                $fundingVaultId,
                $sourceVaultId,
                $feedAmount,
                $job,
                $externalTxId,
                'Native gas feed',
                [
                    'sweep_job_id' => $job->id,
                    'leg' => 'gas_feed',
                    'source_vault_id' => $sourceVaultId,
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->logDiagnostic(
                'error',
                'gas_local_record_failed',
                sprintf('%s gas feed submitted but local fireblocks_transactions row failed: %s', $gasAssetId, $e->getMessage()),
                $job,
                context: [
                    'fireblocks_tx_id' => $transaction->id ?? null,
                    'funding_vault_id' => $fundingVaultId,
                    'source_vault_id' => $sourceVaultId,
                    'error' => $e->getMessage(),
                ]
            );
        }

        $this->logger->log('gas_feed_submitted', sprintf('Waiting for %s gas feed from funding vault', $gasAssetId), $job, context: [
            'funding_vault_id' => $fundingVaultId,
            'feed_amount' => $feedAmount,
            'gas_asset_id' => $gasAssetId,
            'gas_feed_fireblocks_tx_id' => $transaction->id ?? null,
        ]);

        return [
            'status' => FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
            'job_id' => $job->id,
            'gas_feed_fireblocks_tx_id' => $transaction->id ?? null,
            'message' => sprintf('Waiting for %s gas feed before token sweep', $gasAssetId),
        ];
    }

    /**
     * @return array{gas_asset_id: string, available_gas: string, required_gas: string, available_trx?: string, required_trx?: string}
     */
    protected function gasAmountContext(string $gasAssetId, string $availableGas, string $requiredGas): array
    {
        $context = [
            'gas_asset_id' => $gasAssetId,
            'available_gas' => $availableGas,
            'required_gas' => $requiredGas,
        ];

        if (str_contains(strtoupper($gasAssetId), 'TRX')) {
            $context['available_trx'] = $availableGas;
            $context['required_trx'] = $requiredGas;
        }

        return $context;
    }

    public function buildGasFeedExternalTxId(int $jobId): string
    {
        return \App\Modules\Fireblocks\Support\FireblocksTransactionReferences::buildSweepGasExternalTxId($jobId);
    }

    /**
     * Match sweep jobs waiting for gas, stuck gas_funded, or wrongly failed while gas was still pending.
     */
    protected function findSweepJobForGasFeedWebhook(string $fireblocksTxId, array $webhookData): ?FireblocksSweepJob
    {
        $openLegQuery = fn () => FireblocksSweepJob::query()
            ->where(function ($q) {
                $q->whereNull('fireblocks_tx_id')
                    ->orWhere('fireblocks_tx_id', '');
            });

        $statuses = [
            FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
            FireblocksSweepJob::STATUS_GAS_FUNDED,
        ];

        $job = $openLegQuery()
            ->whereIn('status', $statuses)
            ->where('gas_feed_fireblocks_tx_id', $fireblocksTxId)
            ->first();
        if ($job) {
            return $job;
        }

        $externalTxId = (string) ($webhookData['externalTxId'] ?? '');
        if ($externalTxId !== '' && \App\Modules\Fireblocks\Support\FireblocksTransactionReferences::isVaultSweepGasExternalTxId($externalTxId)) {
            $job = $openLegQuery()
                ->whereIn('status', $statuses)
                ->where('gas_feed_external_tx_id', $externalTxId)
                ->first();
            if ($job) {
                return $job;
            }
        }

        $failedMatch = fn () => $openLegQuery()
            ->where('status', FireblocksSweepJob::STATUS_FAILED)
            ->where(function ($q) use ($fireblocksTxId, $externalTxId) {
                $q->where('gas_feed_fireblocks_tx_id', $fireblocksTxId);
                if ($externalTxId !== '') {
                    $q->orWhere('gas_feed_external_tx_id', $externalTxId);
                }
            });

        $job = $failedMatch()
            ->whereIn('skip_reason', self::recoverableGasWaitSkipReasons())
            ->first();
        if ($job) {
            return $job;
        }

        // e.g. token sweep failed with "Source balance error" when gas feed was still in flight
        return $failedMatch()
            ->where(function ($q) {
                $q->whereIn('skip_reason', self::recoverablePrematureTokenSweepSkipReasons())
                    ->orWhere('skip_reason', 'like', 'Source balance%');
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return list<string>
     */
    public static function recoverableGasWaitSkipReasons(): array
    {
        return [
            'missing_fireblocks_tx_id',
            'stale_submitted',
            'stale_waiting_for_gas',
        ];
    }

    /**
     * @return list<string>
     */
    public static function recoverablePrematureTokenSweepSkipReasons(): array
    {
        return [
            'Source balance error',
        ];
    }

    protected function resumeTokenSweepAfterGasFeed(FireblocksSweepJob $job): void
    {
        $rule = FireblocksSweepRule::query()->active()->where('id', $job->rule_id)->first();
        if (!$rule) {
            $job->update([
                'status' => FireblocksSweepJob::STATUS_SKIPPED,
                'skip_reason' => 'rule_disabled',
            ]);
            Log::warning('Fireblocks gas feed — token sweep skipped, rule inactive', [
                'sweep_job_id' => $job->id,
                'rule_id' => $job->rule_id,
            ]);

            return;
        }

        try {
            $result = app(SweepExecutionService::class)->resumeAfterGasFeed($job, $rule);
            Log::info('Fireblocks gas feed — token sweep resume finished', [
                'sweep_job_id' => $job->id,
                'status' => $result['status'] ?? null,
                'reason' => $result['reason'] ?? null,
                'fireblocks_tx_id' => $result['fireblocks_tx_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Fireblocks gas feed — token sweep resume failed', [
                'sweep_job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public static function isGasFeedTransaction(array $data): bool
    {
        $externalTxId = trim((string) ($data['externalTxId'] ?? ''));
        if ($externalTxId !== '' && \App\Modules\Fireblocks\Support\FireblocksTransactionReferences::isVaultSweepGasExternalTxId($externalTxId)) {
            return true;
        }

        $note = trim((string) ($data['note'] ?? ''));
        if ($note !== '' && stripos($note, 'gas feed') !== false) {
            return true;
        }

        $fireblocksTxId = trim((string) ($data['id'] ?? ''));
        if ($fireblocksTxId !== '') {
            return FireblocksSweepJob::query()
                ->where('gas_feed_fireblocks_tx_id', $fireblocksTxId)
                ->exists();
        }

        return false;
    }

    protected function logGasFeedWebhookStatus(
        FireblocksSweepJob $job,
        string $status,
        string $fireblocksTxId,
        array $webhookData,
        \Carbon\CarbonInterface $at
    ): void {
        $subStatus = isset($webhookData['subStatus']) ? strtoupper((string) $webhookData['subStatus']) : null;

        $this->logger->log(
            'webhook_status_update',
            sprintf(
                'Gas feed webhook — status %s%s at %s',
                $status,
                $subStatus ? ' / ' . $subStatus : '',
                $at->format('Y-m-d H:i:s')
            ),
            $job,
            level: in_array($status, ['FAILED', 'REJECTED', 'CANCELLED', 'BLOCKED'], true) ? 'error' : 'info',
            context: [
                'leg' => 'gas_feed',
                'fireblocks_tx_id' => $fireblocksTxId,
                'fireblocks_status' => $status,
                'fireblocks_sub_status' => $subStatus,
                'webhook_at' => $at->toDateTimeString(),
            ]
        );
    }
}
