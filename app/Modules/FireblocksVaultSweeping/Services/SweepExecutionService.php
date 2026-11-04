<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Support\SweepAssetMetadata;
use App\Modules\FireblocksVaultSweeping\Support\SweepDestination;
use App\Modules\FireblocksVaultSweeping\Support\SweepFireblocksDiagnostics;
use App\Modules\FireblocksVaultSweeping\Support\SweepFireblocksFeeEstimate;
use App\Modules\FireblocksVaultSweeping\Support\SweepFireblocksTransactions;
use App\Modules\FireblocksVaultSweeping\Support\SweepJobReason;
use App\Modules\FireblocksVaultSweeping\Support\SweepLocalTransactionRecorder;
use App\Modules\FireblocksVaultSweeping\Support\SweepMath;
use Fireblocks\Sdk\Facades\Fireblocks;
use Fireblocks\Sdk\Models\TransactionRequest;
use Illuminate\Support\Facades\Log;

class SweepExecutionService
{
    protected ?string $lastFireblocksStep = null;

    public function __construct(
        protected SweepLogService $logger,
        protected ?SweepBalanceService $balances = null,
        protected ?TronGasFundingService $tronGas = null,
        protected ?SweepFireblocksTransactions $fireblocksTx = null,
    ) {
        $this->balances = $balances ?? app(SweepBalanceService::class);
        $this->tronGas = $tronGas ?? app(TronGasFundingService::class);
        $this->fireblocksTx = $fireblocksTx ?? app(SweepFireblocksTransactions::class);
    }

    public function hasInFlightSweep(
        string $sourceVaultId,
        string $assetId,
        ?string $triggerSource = null,
        ?string $depositFireblocksTxId = null
    ): bool {
        return $this->findBlockingInFlightJob($sourceVaultId, $assetId, $triggerSource, $depositFireblocksTxId) !== null;
    }

    public function findBlockingInFlightJob(
        string $sourceVaultId,
        string $assetId,
        ?string $triggerSource = null,
        ?string $depositFireblocksTxId = null
    ): ?FireblocksSweepJob {
        $this->releaseStaleLocalInFlightJobs($sourceVaultId, $assetId);

        app(SweepJobReconciliationService::class)->reconcileVaultAsset($sourceVaultId, $assetId);

        $active = FireblocksSweepJob::query()
            ->where('source_vault_id', $sourceVaultId)
            ->where('asset_id', $assetId)
            ->whereIn('status', FireblocksSweepJob::ACTIVE_FIREBLOCKS_STATUSES)
            ->orderByDesc('id')
            ->first();

        if ($active !== null) {
            $this->attemptGasRecoveryForActiveJob($active);

            $active = FireblocksSweepJob::query()
                ->where('source_vault_id', $sourceVaultId)
                ->where('asset_id', $assetId)
                ->whereIn('status', FireblocksSweepJob::ACTIVE_FIREBLOCKS_STATUSES)
                ->orderByDesc('id')
                ->first();

            if ($active !== null) {
                return $active;
            }
        }

        $localQuery = FireblocksSweepJob::query()
            ->where('source_vault_id', $sourceVaultId)
            ->where('asset_id', $assetId)
            ->whereIn('status', FireblocksSweepJob::LOCAL_IN_FLIGHT_STATUSES);

        if ($triggerSource === 'deposit' && trim((string) $depositFireblocksTxId) !== '') {
            $localQuery->where('deposit_fireblocks_tx_id', $depositFireblocksTxId);
        }

        return $localQuery->orderByDesc('id')->first();
    }

    protected function attemptGasRecoveryForActiveJob(FireblocksSweepJob $job): void
    {
        if (trim((string) ($job->fireblocks_tx_id ?? '')) !== '') {
            return;
        }

        $needsRecovery = in_array($job->status, [
            FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
            FireblocksSweepJob::STATUS_GAS_FUNDED,
        ], true) || (
            $job->status === FireblocksSweepJob::STATUS_WAITING_FOR_GAS
            && trim((string) ($job->gas_feed_fireblocks_tx_id ?? '')) === ''
        );

        if (!$needsRecovery) {
            return;
        }

        try {
            $this->tronGas->recoverGasLegForJob($job);
        } catch (\Throwable $e) {
            Log::warning('[Sweep] Gas recovery failed while checking in-flight job', [
                'sweep_job_id' => $job->id,
                'status' => $job->status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fail local pending/queued jobs that were never submitted to Fireblocks.
     */
    public function releaseStaleLocalInFlightJobs(
        string $sourceVaultId,
        string $assetId,
        int $staleMinutes = 15
    ): int {
        $cutoff = now()->subMinutes(max(1, $staleMinutes));

        return FireblocksSweepJob::query()
            ->where('source_vault_id', $sourceVaultId)
            ->where('asset_id', $assetId)
            ->whereIn('status', FireblocksSweepJob::LOCAL_IN_FLIGHT_STATUSES)
            ->where('updated_at', '<', $cutoff)
            ->update([
                'status' => FireblocksSweepJob::STATUS_FAILED,
                'skip_reason' => 'stale_in_flight',
                'failed_at' => now(),
            ]);
    }

    public function execute(
        FireblocksSweepRule $rule,
        string $sourceVaultId,
        string $assetId,
        string $triggerSource,
        ?string $depositTxId = null,
        ?array $config = null,
        ?FireblocksSweepJob $existingJob = null
    ): array {
        if (!$rule->isRunnable()) {
            return [
                'status' => FireblocksSweepJob::STATUS_SKIPPED,
                'reason' => 'rule_disabled',
                'message' => 'Rule is disabled or archived',
            ];
        }

        $config = $config ?? SweepConfigService::tryGet() ?? [];
        $destinationVaultId = SweepDestination::resolveVaultId($rule, $config);

        if (!SweepDestination::isValidForSource($destinationVaultId, $sourceVaultId)) {
            return $this->skip($existingJob, $rule, $sourceVaultId, $assetId, $triggerSource, $depositTxId, 'invalid_destination');
        }

        if ($existingJob !== null) {
            $job = $existingJob;
            $externalTxId = $job->external_tx_id ?: $this->buildExternalTxId(
                $rule->id,
                $sourceVaultId,
                $assetId,
                $triggerSource,
                $depositTxId
            );
            if (!$job->external_tx_id) {
                $job->update(['external_tx_id' => $externalTxId]);
            }
            if ($job->status === FireblocksSweepJob::STATUS_QUEUED) {
                $job->update(['status' => FireblocksSweepJob::STATUS_PENDING]);
            }
        } else {
            $externalTxId = $this->buildExternalTxId($rule->id, $sourceVaultId, $assetId, $triggerSource, $depositTxId);
            if ($this->shouldSkipDuplicateExternalTxId($externalTxId, $triggerSource)) {
                return $this->skip(null, $rule, $sourceVaultId, $assetId, $triggerSource, $depositTxId, 'duplicate_external_tx_id');
            }

            $job = FireblocksSweepJob::create([
                'rule_id' => $rule->id,
                'source_vault_id' => $sourceVaultId,
                'destination_vault_id' => $destinationVaultId,
                'asset_id' => $assetId,
                'trigger_source' => $triggerSource,
                'deposit_fireblocks_tx_id' => $depositTxId,
                'status' => FireblocksSweepJob::STATUS_PENDING,
                'external_tx_id' => $externalTxId,
            ]);
        }

        $dryRun = !empty($config['dry_run_mode']) || $rule->dry_run;

        try {
            $transferAssetId = $rule->targetAssetId() ?? $assetId;
            $live = $this->resolveLiveSweepAmount(
                $rule,
                $sourceVaultId,
                $assetId,
                $destinationVaultId,
                $triggerSource,
                $depositTxId,
                $job,
                $config,
                true
            );

            if (($live['skip_reason'] ?? null) !== null) {
                return $this->skip(
                    $job,
                    $rule,
                    $sourceVaultId,
                    $assetId,
                    $triggerSource,
                    $depositTxId,
                    (string) $live['skip_reason'],
                    $live['skip_context'] ?? []
                );
            }

            $sweepAmount = (string) $live['amount'];
            $balance = (string) $live['available'];

            if ($dryRun) {
                if ($this->tronGas->requiresTronGasFunding($rule, $assetId)) {
                    $this->tronGas->ensureGasFunded(
                        $job,
                        $rule,
                        $sourceVaultId,
                        $assetId,
                        $sweepAmount,
                        $destinationVaultId,
                        $rule->targetAssetId() ?? $assetId,
                        $config,
                        true
                    );
                }

                $job->update([
                    'status' => FireblocksSweepJob::STATUS_SKIPPED,
                    'skip_reason' => 'dry_run',
                    'amount' => $sweepAmount,
                    'fee_estimate' => null,
                ]);
                $this->logger->log('sweep_dry_run', 'Dry run — no transaction created', $job, null, null, null, 'info', [
                    'amount' => $sweepAmount,
                ]);

                return [
                    'status' => FireblocksSweepJob::STATUS_SKIPPED,
                    'reason' => 'dry_run',
                    'amount' => $sweepAmount,
                    'source_vault_id' => $sourceVaultId,
                    'destination_vault_id' => $destinationVaultId,
                    'asset_id' => $assetId,
                    'available_before' => $balance,
                    'available_after' => SweepMath::sub($balance, $sweepAmount, 18),
                ];
            }

            if ($this->tronGas->requiresTronGasFunding($rule, $assetId)) {
                $gasResult = $this->tronGas->ensureGasFunded(
                    $job,
                    $rule,
                    $sourceVaultId,
                    $assetId,
                    $sweepAmount,
                    $destinationVaultId,
                    $transferAssetId,
                    $config,
                    false
                );

                if (($gasResult['status'] ?? '') === FireblocksSweepJob::STATUS_WAITING_FOR_GAS) {
                    return $gasResult;
                }

                if (($gasResult['status'] ?? '') === 'skipped') {
                    return $this->skip($job, $rule, $sourceVaultId, $assetId, $triggerSource, $depositTxId, (string) ($gasResult['reason'] ?? 'gas_funding_skipped'), $gasResult);
                }
            }

            return $this->submitSweepTransaction(
                $job,
                $rule,
                $sourceVaultId,
                $assetId,
                $transferAssetId,
                $destinationVaultId,
                $sweepAmount,
                $triggerSource,
                $depositTxId,
                $externalTxId
            );
        } catch (\Throwable $e) {
            $failureContext = SweepFireblocksDiagnostics::executionFailureContext($e, [
                'rule_id' => $rule->id,
                'job_id' => $job->id ?? null,
                'source_vault_id' => $sourceVaultId,
                'destination_vault_id' => $destinationVaultId ?? null,
                'asset_id' => $assetId,
                'target_asset_id' => $rule->targetAssetId(),
                'trigger_source' => $triggerSource,
                'fireblocks_step' => $this->lastFireblocksStep,
            ]);

            Log::error('Fireblocks sweep execution failed', $failureContext);

            $job->update([
                'status' => FireblocksSweepJob::STATUS_FAILED,
                'skip_reason' => SweepJobReason::fromThrowable($e),
            ]);

            $this->logger->log('sweep_failed', SweepFireblocksDiagnostics::formatExceptionMessage($e, 'Fireblocks sweep execution failed'), $job, level: 'error', context: $failureContext);

            return ['status' => FireblocksSweepJob::STATUS_FAILED, 'message' => $e->getMessage()];
        }
    }

    public function resumeAfterGasFeed(FireblocksSweepJob $job, FireblocksSweepRule $rule): array
    {
        if (!$rule->isRunnable()) {
            $job->update([
                'status' => FireblocksSweepJob::STATUS_SKIPPED,
                'skip_reason' => 'rule_disabled',
            ]);

            return ['status' => FireblocksSweepJob::STATUS_SKIPPED, 'reason' => 'rule_disabled'];
        }

        try {
            $config = SweepConfigService::tryGet() ?? [];
        $destinationVaultId = SweepDestination::resolveVaultId($rule, $config)
            ?? trim((string) $job->destination_vault_id);

        if (!SweepDestination::isValidForSource($destinationVaultId, $job->source_vault_id)) {
            $job->update([
                'status' => FireblocksSweepJob::STATUS_SKIPPED,
                'skip_reason' => 'invalid_destination',
            ]);

            return ['status' => FireblocksSweepJob::STATUS_SKIPPED, 'reason' => 'invalid_destination'];
        }

        $live = $this->resolveLiveSweepAmount(
            $rule,
            $job->source_vault_id,
            $job->asset_id,
            $destinationVaultId,
            $job->trigger_source,
            $job->deposit_fireblocks_tx_id,
            $job,
            $config,
            false
        );

        if (($live['skip_reason'] ?? null) !== null || SweepMath::comp((string) ($live['amount'] ?? '0'), '0', 18) <= 0) {
            $job->update([
                'status' => FireblocksSweepJob::STATUS_SKIPPED,
                'skip_reason' => (string) ($live['skip_reason'] ?? 'insufficient_after_gas_feed'),
            ]);

            return ['status' => FireblocksSweepJob::STATUS_SKIPPED, 'reason' => $live['skip_reason'] ?? 'insufficient_after_gas_feed'];
        }

        $sweepAmount = (string) $live['amount'];
        $transferAssetId = $rule->targetAssetId() ?? $job->asset_id;

        $externalTxId = trim((string) ($job->external_tx_id ?? ''));
        if ($externalTxId === '') {
            $externalTxId = $this->buildExternalTxId(
                (int) $job->rule_id,
                $job->source_vault_id,
                $job->asset_id,
                $job->trigger_source,
                $job->deposit_fireblocks_tx_id
            );
        }
        $this->logger->log('sweep_resuming_after_gas', 'Submitting token sweep after native gas feed confirmed', $job, context: [
            'sweep_amount' => $sweepAmount,
        ]);

        return $this->submitSweepTransaction(
            $job,
            $rule,
            $job->source_vault_id,
            $job->asset_id,
            $transferAssetId,
            $destinationVaultId,
            $sweepAmount,
            $job->trigger_source,
            $job->deposit_fireblocks_tx_id,
            $externalTxId
        );
        } catch (\Throwable $e) {
            $failureContext = SweepFireblocksDiagnostics::executionFailureContext($e, [
                'job_id' => $job->id,
                'rule_id' => $rule->id,
                'source_vault_id' => $job->source_vault_id,
                'asset_id' => $job->asset_id,
                'fireblocks_step' => $this->lastFireblocksStep,
            ]);

            Log::error('Fireblocks sweep resume after gas feed failed', $failureContext);

            $job->update([
                'status' => FireblocksSweepJob::STATUS_FAILED,
                'skip_reason' => SweepJobReason::fromThrowable($e),
            ]);

            $this->logger->log('sweep_failed', SweepFireblocksDiagnostics::formatExceptionMessage($e, 'Fireblocks sweep execution failed'), $job, level: 'error', context: $failureContext);

            return ['status' => FireblocksSweepJob::STATUS_FAILED, 'message' => $e->getMessage()];
        }
    }

    protected function submitSweepTransaction(
        FireblocksSweepJob $job,
        FireblocksSweepRule $rule,
        string $sourceVaultId,
        string $assetId,
        string $transferAssetId,
        string $destinationVaultId,
        string $sweepAmount,
        string $triggerSource,
        ?string $depositTxId,
        string $externalTxId
    ): array {
        $live = $this->resolveLiveSweepAmount(
            $rule,
            $sourceVaultId,
            $assetId,
            $destinationVaultId,
            $triggerSource,
            $depositTxId,
            $job,
            null,
            false
        );

        if (($live['skip_reason'] ?? null) !== null || SweepMath::comp((string) ($live['amount'] ?? '0'), '0', 18) <= 0) {
            throw new \RuntimeException(
                'Sweep amount not viable after balance refresh: ' . ($live['skip_reason'] ?? 'zero_amount')
            );
        }

        $refreshedAmount = (string) $live['amount'];
        if (SweepMath::comp($refreshedAmount, $sweepAmount, 18) !== 0) {
            $this->logger->logDiagnostic(
                'info',
                'sweep_amount_refreshed_at_submit',
                'Sweep amount updated from refreshed vault balance before Fireblocks submit',
                $job,
                $sourceVaultId,
                $assetId,
                [
                    'rule_id' => $rule->id,
                    'previous_amount' => $sweepAmount,
                    'refreshed_amount' => $refreshedAmount,
                    'available' => $live['available'] ?? null,
                    'max_spendable' => $live['max_spendable'] ?? null,
                ]
            );
        }
        $sweepAmount = $refreshedAmount;

        $availableNow = $this->fetchAvailableBalance($sourceVaultId, $transferAssetId);
        $sweepAmount = SweepMath::forTransactionAmount($sweepAmount, $availableNow);

        $txRequest = new TransactionRequest();
        $txRequest
            ->withAsset($transferAssetId)
            ->fromVaultAccount($sourceVaultId)
            ->toVaultAccount($destinationVaultId)
            ->withAmount($sweepAmount)
            ->withFeeLevel($rule->feeLevel())
            ->withExternalTxId($externalTxId)
            ->withNote($this->formatSweepTransactionNote($rule->id, $sourceVaultId));

        $useGasRelay = $rule->shouldUseGasless($assetId);
        $treatAsGross = !$useGasRelay && $rule->shouldTreatAsGrossAmount($assetId);

        if (SweepMath::comp($refreshedAmount, $sweepAmount, 18) !== 0) {
            $this->logger->logDiagnostic(
                'info',
                'sweep_amount_sanitized_for_submit',
                'Sweep amount adjusted to avoid float precision over available balance',
                $job,
                $sourceVaultId,
                $assetId,
                [
                    'rule_id' => $rule->id,
                    'before' => $refreshedAmount,
                    'after' => $sweepAmount,
                    'available' => $availableNow,
                ]
            );
        }
        $txRequest->withAmount($sweepAmount);

        if (SweepMath::comp($sweepAmount, '0', 18) <= 0) {
            throw new \RuntimeException('Sweep not viable: zero available balance at submit');
        }

        if ($treatAsGross) {
            $txRequest->treatAsGrossAmount = true;
        }

        $this->lastFireblocksStep = 'create_transaction';
        $endpoint = '/v1/transactions';
        $this->logger->logFireblocksRequest(
            'create_transaction',
            'POST',
            $endpoint,
            $txRequest,
            $job,
            $sourceVaultId,
            $assetId,
            [
                'transfer_asset_id' => $transferAssetId,
                'external_tx_id' => $externalTxId,
                'use_gasless' => $useGasRelay,
                'treat_as_gross_amount' => $treatAsGross,
                'fee_level' => $rule->feeLevel(),
                'gas_funding_mode' => $useGasRelay ? 'gas_relay' : 'native',
            ],
            $useGasRelay
        );

        try {
            $transaction = $this->fireblocksTx->create($txRequest, $useGasRelay);
        } catch (\Throwable $e) {
            $failureContext = SweepFireblocksDiagnostics::executionFailureContext($e, array_merge(
                SweepFireblocksDiagnostics::apiRequestContext('POST', $endpoint, $txRequest, $useGasRelay),
                [
                'rule_id' => $rule->id,
                'job_id' => $job->id,
                'source_vault_id' => $sourceVaultId,
                'destination_vault_id' => $destinationVaultId,
                'asset_id' => $assetId,
                'transfer_asset_id' => $transferAssetId,
                'sweep_amount' => $sweepAmount,
                'external_tx_id' => $externalTxId,
                'fireblocks_step' => 'create_transaction',
                ]
            ));

            Log::error('Fireblocks sweep transaction create failed', $failureContext);
            $this->logger->log(
                'sweep_failed',
                SweepFireblocksDiagnostics::formatExceptionMessage($e, 'Fireblocks create transaction failed'),
                $job,
                level: 'error',
                context: $failureContext
            );

            throw $e;
        }

        $job->update([
            'status' => FireblocksSweepJob::STATUS_SUBMITTED,
            'amount' => $sweepAmount,
            'fee_estimate' => null,
            'fireblocks_tx_id' => $transaction->id ?? null,
            'fireblocks_status' => $transaction->status ?? 'SUBMITTED',
            'fireblocks_sub_status' => $transaction->subStatus ?? null,
            'fireblocks_status_updated_at' => now(),
        ]);

        try {
            SweepLocalTransactionRecorder::recordVaultTransfer(
                $transaction,
                $transferAssetId,
                $sourceVaultId,
                $destinationVaultId,
                $sweepAmount,
                $job,
                $externalTxId,
                'Automated vault sweep',
                [
                    'sweep_rule_id' => $rule->id,
                    'trigger_source' => $triggerSource,
                    'leg' => 'token_sweep',
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->logDiagnostic(
                'error',
                'sweep_local_record_failed',
                'Fireblocks sweep submitted but local fireblocks_transactions row failed: ' . $e->getMessage(),
                $job,
                context: [
                    'fireblocks_tx_id' => $transaction->id ?? null,
                    'destination_vault_id' => $destinationVaultId,
                    'error' => $e->getMessage(),
                ]
            );
        }

        $this->logger->log('sweep_submitted', 'Vault sweep transaction submitted', $job, context: [
            'fireblocks_tx_id' => $transaction->id ?? null,
            'fireblocks_status' => $transaction->status ?? 'SUBMITTED',
            'amount' => $sweepAmount,
            'submitted_at' => now()->toDateTimeString(),
        ]);

        return [
            'status' => FireblocksSweepJob::STATUS_SUBMITTED,
            'job_id' => $job->id,
            'fireblocks_tx_id' => $transaction->id ?? null,
            'amount' => $sweepAmount,
        ];
    }

    public function buildExternalTxId(
        int $ruleId,
        string $sourceVaultId,
        string $assetId,
        string $triggerSource = 'scheduled',
        ?string $depositTxId = null
    ): string {
        if ($triggerSource === 'manual' || $triggerSource === 'scheduled') {
            $uniquePart = now()->format('YmdHis') . '-' . substr(str_replace('.', '', uniqid('', true)), -10);
        } elseif ($triggerSource === 'deposit' && trim((string) $depositTxId) !== '') {
            $uniquePart = 'dep-' . substr(hash('sha256', (string) $depositTxId), 0, 32);
        } else {
            $uniquePart = now()->format('YmdHis') . '-' . substr(str_replace('.', '', uniqid('', true)), -10);
        }

        $key = \App\Modules\Fireblocks\Support\FireblocksTransactionReferences::buildSweepExternalTxId(
            $ruleId,
            $sourceVaultId,
            $assetId,
            $uniquePart
        );

        return substr($key, 0, 255);
    }

    /**
     * Deposit sweeps: block replays of the same deposit idempotency key.
     * Scheduled/manual: only block while a job with the same external tx id is still in flight.
     */
    protected function shouldSkipDuplicateExternalTxId(string $externalTxId, string $triggerSource): bool
    {
        $query = FireblocksSweepJob::query()->where('external_tx_id', $externalTxId);

        if ($triggerSource === 'deposit') {
            return $query->exists();
        }

        return $query->whereIn('status', FireblocksSweepJob::IN_FLIGHT_STATUSES)->exists();
    }

    /**
     * Create a queued sweep job record (used for delayed deposit webhooks).
     */
    public function createQueuedJob(
        FireblocksSweepRule $rule,
        string $sourceVaultId,
        string $assetId,
        string $triggerSource,
        ?string $depositTxId,
        ?array $config = null
    ): FireblocksSweepJob {
        $config = $config ?? SweepConfigService::tryGet() ?? [];
        $destinationVaultId = SweepDestination::resolveVaultId($rule, $config);

        return FireblocksSweepJob::create([
            'rule_id' => $rule->id,
            'source_vault_id' => $sourceVaultId,
            'destination_vault_id' => $destinationVaultId,
            'asset_id' => $assetId,
            'trigger_source' => $triggerSource,
            'deposit_fireblocks_tx_id' => $depositTxId,
            'status' => FireblocksSweepJob::STATUS_QUEUED,
            'external_tx_id' => $this->buildExternalTxId($rule->id, $sourceVaultId, $assetId, $triggerSource, $depositTxId),
        ]);
    }

    /**
     * Simulate sweep outcome without creating jobs or Fireblocks transactions.
     *
     * @return array<string, mixed>
     */
    public function planSweep(
        FireblocksSweepRule $rule,
        string $sourceVaultId,
        string $assetId,
        ?array $config = null,
        bool $refreshBalance = false,
        string $triggerSource = 'scheduled'
    ): array {
        $config = $config ?? SweepConfigService::tryGet() ?? [];
        $destinationVaultId = SweepDestination::resolveVaultId($rule, $config);

        if (!SweepDestination::isValidForSource($destinationVaultId, $sourceVaultId)) {
            return $this->planResult($sourceVaultId, $assetId, $destinationVaultId ?? '', '0', '0', '0', false, 'invalid_destination');
        }

        if ($this->hasInFlightSweep($sourceVaultId, $assetId)) {
            $snap = $this->balances->getVaultAssetBalance($sourceVaultId, $assetId, false);

            return $this->planResult(
                $sourceVaultId,
                $assetId,
                $destinationVaultId,
                $snap['available'],
                $snap['total'],
                '0',
                false,
                'in_flight',
                $snap
            );
        }

        try {
            $snap = $this->balances->getVaultAssetBalance($sourceVaultId, $assetId, $refreshBalance);
            if (!$snap['found']) {
                return $this->planResult($sourceVaultId, $assetId, $destinationVaultId, '0', '0', '0', false, 'asset_not_on_vault', $snap);
            }

            $balance = $snap['available'];
            $total = $snap['total'];
            $reserve = '0';
            $threshold = $this->resolveThreshold($rule, $config, $triggerSource);

            if (SweepMath::comp($balance, '0', 18) <= 0) {
                return $this->planResult($sourceVaultId, $assetId, $destinationVaultId, $balance, $total, '0', false, 'zero_balance', $snap, '0', $reserve, $threshold);
            }

            $afterReserve = SweepMath::sub($balance, $reserve, 18);
            if (SweepMath::comp($afterReserve, '0', 18) <= 0) {
                return $this->planResult($sourceVaultId, $assetId, $destinationVaultId, $balance, $total, '0', false, 'insufficient_after_reserve', $snap, '0', $reserve, $threshold);
            }

            if (SweepMath::comp($afterReserve, $threshold, 18) < 0) {
                return $this->planResult($sourceVaultId, $assetId, $destinationVaultId, $balance, $total, '0', false, 'below_threshold', $snap, '0', $reserve, $threshold);
            }

            $maxSpendable = $this->resolveSweepCap($sourceVaultId, $assetId, $balance, $rule, $triggerSource, null, null);
            $sweepAmount = $this->calculateSweepAmount($balance, $maxSpendable, $reserve);

            if (SweepMath::comp($sweepAmount, '0', 18) <= 0) {
                return $this->planResult($sourceVaultId, $assetId, $destinationVaultId, $balance, $total, '0', false, 'insufficient_after_reserve', $snap, $maxSpendable, $reserve, $threshold);
            }

            if (SweepMath::comp($sweepAmount, $threshold, 18) < 0) {
                return $this->planResult($sourceVaultId, $assetId, $destinationVaultId, $balance, $total, '0', false, 'below_threshold', $snap, $maxSpendable, $reserve, $threshold);
            }

            if ($rule->shouldTreatAsGrossAmount($assetId) && !$rule->shouldUseGasless($assetId)) {
                $transferAssetId = $rule->targetAssetId() ?? $assetId;
                $grossFinal = $this->finalizeNativeGrossSweepAmount(
                    $rule,
                    $sourceVaultId,
                    $transferAssetId,
                    $destinationVaultId,
                    $sweepAmount,
                    null
                );
                if (($grossFinal['skip_reason'] ?? null) !== null) {
                    return $this->planResult(
                        $sourceVaultId,
                        $assetId,
                        $destinationVaultId,
                        $balance,
                        $total,
                        '0',
                        false,
                        (string) $grossFinal['skip_reason'],
                        $snap,
                        $maxSpendable,
                        $reserve,
                        $threshold
                    );
                }
                $sweepAmount = (string) ($grossFinal['amount'] ?? $sweepAmount);
            }

            $availableAfter = SweepMath::sub($balance, $sweepAmount, 18);
            $totalAfter = SweepMath::sub($total, $sweepAmount, 18);

            return [
                'source_vault_id' => $sourceVaultId,
                'destination_vault_id' => $destinationVaultId,
                'asset_id' => $assetId,
                'available_before' => $balance,
                'total_before' => $total,
                'available_after' => $availableAfter,
                'total_after' => $totalAfter,
                'max_spendable' => $maxSpendable,
                'reserve' => $reserve,
                'threshold' => $threshold,
                'sweep_amount' => $sweepAmount,
                'would_sweep' => true,
                'skip_reason' => null,
            ];
        } catch (\Throwable $e) {
            $failureContext = SweepFireblocksDiagnostics::executionFailureContext($e, [
                'rule_id' => $rule->id,
                'source_vault_id' => $sourceVaultId,
                'asset_id' => $assetId,
                'fireblocks_step' => $this->lastFireblocksStep ?? 'get_max_spendable_amount',
            ]);

            $this->logger->logDiagnostic(
                'warning',
                'sweep_plan_fireblocks_error',
                'Sweep plan simulation Fireblocks error',
                null,
                $sourceVaultId,
                $assetId,
                $failureContext
            );

            return $this->planResult($sourceVaultId, $assetId, $destinationVaultId, '0', '0', '0', false, 'error: ' . $e->getMessage());
        }
    }

    protected function planResult(
        string $sourceVaultId,
        string $assetId,
        string $destinationVaultId,
        string $availableBefore,
        string $totalBefore,
        string $sweepAmount,
        bool $wouldSweep,
        ?string $skipReason,
        ?array $snap = null,
        ?string $maxSpendable = null,
        ?string $reserve = null,
        ?string $threshold = null
    ): array {
        return [
            'source_vault_id' => $sourceVaultId,
            'destination_vault_id' => $destinationVaultId,
            'asset_id' => $assetId,
            'available_before' => $availableBefore,
            'total_before' => $totalBefore,
            'available_after' => $availableBefore,
            'total_after' => $totalBefore,
            'max_spendable' => $maxSpendable ?? '0',
            'reserve' => $reserve ?? '0',
            'threshold' => $threshold ?? '0',
            'sweep_amount' => $sweepAmount,
            'would_sweep' => $wouldSweep,
            'skip_reason' => $skipReason,
            'balance_found' => $snap['found'] ?? null,
            'balance_error' => $snap['error'] ?? null,
        ];
    }

    protected function fetchAvailableBalance(string $vaultId, string $assetId): string
    {
        return $this->balances->getVaultAssetBalance($vaultId, $assetId, true)['available'];
    }

    public function resolvePlannedSweepAmountForJob(FireblocksSweepJob $job, FireblocksSweepRule $rule): string
    {
        $config = SweepConfigService::tryGet() ?? [];
        $destinationVaultId = SweepDestination::resolveVaultId($rule, $config)
            ?? trim((string) $job->destination_vault_id);

        $live = $this->resolveLiveSweepAmount(
            $rule,
            $job->source_vault_id,
            $job->asset_id,
            $destinationVaultId,
            $job->trigger_source,
            $job->deposit_fireblocks_tx_id,
            $job,
            $config,
            false
        );

        return (string) ($live['amount'] ?? '0');
    }

    /**
     * Compute sweep amount from refreshed Fireblocks balances (never reuse stale job amounts).
     *
     * @return array{
     *     amount: string,
     *     available: string,
     *     max_spendable: string,
     *     skip_reason?: string,
     *     skip_context?: array<string, mixed>
     * }
     */
    protected function resolveLiveSweepAmount(
        FireblocksSweepRule $rule,
        string $sourceVaultId,
        string $assetId,
        string $destinationVaultId,
        string $triggerSource,
        ?string $depositTxId,
        ?FireblocksSweepJob $job,
        ?array $config = null,
        bool $applyThreshold = false
    ): array {
        $config = $config ?? SweepConfigService::tryGet() ?? [];
        $balance = $this->fetchAvailableBalance($sourceVaultId, $assetId);
        $maxSpendable = $this->resolveSweepCap($sourceVaultId, $assetId, $balance, $rule, $triggerSource, $depositTxId, $job);
        $reserve = '0';

        $sweepAmount = $this->calculateSweepAmount($balance, $maxSpendable, $reserve);

        if (SweepMath::comp($sweepAmount, '0', 18) <= 0) {
            return [
                'amount' => '0',
                'available' => $balance,
                'max_spendable' => $maxSpendable,
                'skip_reason' => 'insufficient_after_reserve',
                'skip_context' => [
                    'balance' => $balance,
                    'max_spendable' => $maxSpendable,
                    'reserve' => $reserve,
                ],
            ];
        }

        if ($applyThreshold) {
            $threshold = $this->resolveThreshold($rule, $config, $triggerSource);
            if (SweepMath::comp($sweepAmount, $threshold, 18) < 0) {
                return [
                    'amount' => $sweepAmount,
                    'available' => $balance,
                    'max_spendable' => $maxSpendable,
                    'skip_reason' => 'below_threshold',
                    'skip_context' => [
                        'amount' => $sweepAmount,
                        'threshold' => $threshold,
                        'threshold_type' => $this->thresholdTypeForTrigger($triggerSource),
                        'trigger_source' => $triggerSource,
                    ],
                ];
            }
        }

        $transferAssetId = $rule->targetAssetId() ?? $assetId;
        if ($rule->shouldTreatAsGrossAmount($assetId) && !$rule->shouldUseGasless($assetId)) {
            $grossFinal = $this->finalizeNativeGrossSweepAmount(
                $rule,
                $sourceVaultId,
                $transferAssetId,
                $destinationVaultId,
                $sweepAmount,
                $job
            );
            if (($grossFinal['skip_reason'] ?? null) !== null) {
                return [
                    'amount' => '0',
                    'available' => $balance,
                    'max_spendable' => $maxSpendable,
                    'skip_reason' => (string) $grossFinal['skip_reason'],
                    'skip_context' => $grossFinal,
                ];
            }
            $sweepAmount = (string) ($grossFinal['amount'] ?? $sweepAmount);
            if (SweepMath::comp($sweepAmount, '0', 18) <= 0) {
                return [
                    'amount' => '0',
                    'available' => $balance,
                    'max_spendable' => $maxSpendable,
                    'skip_reason' => 'native_gross_zero_amount',
                    'skip_context' => $grossFinal,
                ];
            }
        }

        return [
            'amount' => $sweepAmount,
            'available' => $balance,
            'max_spendable' => $maxSpendable,
        ];
    }

    protected function resolveSweepCap(
        string $sourceVaultId,
        string $assetId,
        string $availableBalance,
        ?FireblocksSweepRule $rule = null,
        ?string $triggerSource = null,
        ?string $depositTxId = null,
        ?FireblocksSweepJob $job = null
    ): string {
        if ($rule !== null && $rule->shouldTreatAsGrossAmount($assetId) && !$rule->shouldUseGasless($assetId)) {
            return $availableBalance;
        }

        if ($rule !== null && $rule->usesAvailableBalanceForSweep()) {
            return $availableBalance;
        }

        return $this->fetchMaxSpendableAmount(
            $sourceVaultId,
            $assetId,
            $rule,
            $triggerSource,
            $depositTxId,
            $job
        );
    }

    protected function fetchMaxSpendableAmount(
        string $sourceVaultId,
        string $assetId,
        ?FireblocksSweepRule $rule = null,
        ?string $triggerSource = null,
        ?string $depositTxId = null,
        ?FireblocksSweepJob $job = null
    ): string {
        $this->lastFireblocksStep = 'get_max_spendable_amount';
        $endpoint = SweepFireblocksDiagnostics::maxSpendableEndpoint($sourceVaultId, $assetId);

        $this->logger->logFireblocksRequest(
            'get_max_spendable_amount',
            'GET',
            $endpoint,
            null,
            $job,
            $sourceVaultId,
            $assetId,
            ['rule_id' => $rule?->id]
        );

        $balanceSnap = ['available' => null, 'total' => null, 'found' => null, 'error' => null];

        try {
            $balanceSnap = $this->balances->getVaultAssetBalance($sourceVaultId, $assetId, true);
            $available = (string) ($balanceSnap['available'] ?? '0');
            $max = SweepMath::fromApiDecimal(Fireblocks::vaults()->getMaxSpendableAmount($sourceVaultId, $assetId));
            $max = $this->reconcileMaxSpendableWithAvailable(
                $sourceVaultId,
                $assetId,
                $available,
                $max,
                $rule,
                $job
            );

            if (SweepMath::isPositive($available) && SweepMath::comp($max, $available, 18) > 0) {
                $this->logger->logDiagnostic(
                    'warning',
                    'max_spendable_exceeds_available',
                    'Fireblocks max_spendable exceeds available balance — capping to available',
                    $job,
                    $sourceVaultId,
                    $assetId,
                    [
                        'rule_id' => $rule?->id,
                        'max_spendable' => $max,
                        'available_balance' => $available,
                    ]
                );

                return $available;
            }

            return $max;
        } catch (\Throwable $e) {
            $available = (string) ($balanceSnap['available'] ?? '0');

            if (SweepFireblocksDiagnostics::isUnsupportedAssetError($e) && SweepMath::isPositive($available)) {
                $fallbackContext = [
                    'rule_id' => $rule?->id,
                    'job_id' => $job?->id,
                    'source_vault_id' => $sourceVaultId,
                    'source_vault_name' => $this->balances->vaultName($sourceVaultId),
                    'asset_id' => $assetId,
                    'available_balance' => $available,
                    'endpoint' => 'GET ' . $endpoint,
                    'fallback' => 'using_available_as_max_spendable',
                    'note' => 'max_spendable_amount API unsupported for this asset — proceeding to POST /v1/transactions with available balance.',
                ];

                $this->logger->logDiagnostic(
                    'warning',
                    'max_spendable_fallback',
                    'Fireblocks max_spendable_amount unsupported — using available balance fallback',
                    $job,
                    $sourceVaultId,
                    $assetId,
                    $fallbackContext
                );

                return $available;
            }

            $failureContext = SweepFireblocksDiagnostics::executionFailureContext($e, [
                'rule_id' => $rule?->id,
                'job_id' => $job?->id,
                'source_vault_id' => $sourceVaultId,
                'source_vault_name' => $this->balances->vaultName($sourceVaultId),
                'asset_id' => $assetId,
                'target_asset_id' => $rule?->targetAssetId(),
                'trigger_source' => $triggerSource,
                'deposit_fireblocks_tx_id' => $depositTxId,
                'fireblocks_step' => 'get_max_spendable_amount',
                'endpoint' => 'GET ' . $endpoint,
                'vault_balance_snapshot' => [
                    'available' => $balanceSnap['available'] ?? null,
                    'total' => $balanceSnap['total'] ?? null,
                    'found' => $balanceSnap['found'] ?? null,
                    'error' => $balanceSnap['error'] ?? null,
                ],
            ]);

            $this->logger->logDiagnostic(
                'error',
                'sweep_max_spendable_failed',
                'Fireblocks getMaxSpendableAmount failed: ' . $e->getMessage(),
                $job,
                $sourceVaultId,
                $assetId,
                $failureContext
            );

            throw $e;
        }
    }

    protected function calculateSweepAmount(string $available, string $maxSpendable, string $reserve): string
    {
        $afterReserve = SweepMath::sub($available, $reserve, 18);
        if (SweepMath::comp($afterReserve, '0', 18) <= 0) {
            return '0';
        }

        if (SweepMath::comp($maxSpendable, '0', 18) <= 0) {
            return '0';
        }

        $amount = SweepMath::comp($afterReserve, $maxSpendable, 18) <= 0 ? $afterReserve : $maxSpendable;

        return SweepMath::forTransactionAmount($amount, $available);
    }

    /**
     * Fireblocks max_spendable can lag after deposits (notably BNB/SOL native). Re-fetch once; if still far below available on native assets, use available.
     */
    protected function reconcileMaxSpendableWithAvailable(
        string $sourceVaultId,
        string $assetId,
        string $available,
        string $max,
        ?FireblocksSweepRule $rule = null,
        ?FireblocksSweepJob $job = null
    ): string {
        if (!SweepMath::isPositive($available) || !SweepMath::isPositive($max)) {
            return $max;
        }

        if (SweepMath::comp($available, $max, 18) <= 0) {
            return $max;
        }

        if (!SweepAssetMetadata::isNativeAccountAsset($assetId)) {
            return $max;
        }

        $this->balances->getVaultAssetBalance($sourceVaultId, $assetId, true);
        $refetchedMax = SweepMath::fromApiDecimal(Fireblocks::vaults()->getMaxSpendableAmount($sourceVaultId, $assetId));
        if (SweepMath::comp($refetchedMax, $max, 18) > 0) {
            $max = $refetchedMax;
        }

        if (SweepMath::comp($available, $max, 18) <= 0) {
            return $max;
        }

        $doubleMax = SweepMath::mul($max, '2', 18);
        if (SweepMath::comp($available, $doubleMax, 18) <= 0) {
            return $max;
        }

        $this->logger->logDiagnostic(
            'warning',
            'max_spendable_stale_native_fallback',
            'Native max_spendable far below refreshed available — using available balance as sweep cap',
            $job,
            $sourceVaultId,
            $assetId,
            [
                'rule_id' => $rule?->id,
                'available_balance' => $available,
                'max_spendable' => $max,
                'refetched_max_spendable' => $refetchedMax,
            ]
        );

        return $available;
    }

    /**
     * Cap native gross sweeps to current available balance and skip dust that cannot cover network fee.
     *
     * @return array{amount?: string, skip_reason?: string, available?: string, network_fee?: string|null, net_after_fee?: string, proposed?: string}
     */
    protected function finalizeNativeGrossSweepAmount(
        FireblocksSweepRule $rule,
        string $sourceVaultId,
        string $transferAssetId,
        string $destinationVaultId,
        string $proposedAmount,
        ?FireblocksSweepJob $job = null
    ): array {
        $available = $this->fetchAvailableBalance($sourceVaultId, $transferAssetId);
        $amount = SweepMath::forTransactionAmount($proposedAmount, $available);

        if (SweepMath::comp($amount, '0', 18) <= 0) {
            return [
                'skip_reason' => 'native_gross_insufficient_balance',
                'available' => $available,
                'proposed' => $proposedAmount,
            ];
        }

        $txRequest = new TransactionRequest();
        $txRequest
            ->withAsset($transferAssetId)
            ->fromVaultAccount($sourceVaultId)
            ->toVaultAccount($destinationVaultId)
            ->withAmount($amount)
            ->withFeeLevel($rule->feeLevel());
        $txRequest->treatAsGrossAmount = true;

        $networkFee = '0';
        try {
            $this->lastFireblocksStep = 'estimate_fee';
            $this->logger->logFireblocksRequest(
                'estimate_fee',
                'POST',
                '/v1/transactions/estimate_fee',
                $txRequest,
                $job,
                $sourceVaultId,
                $transferAssetId,
                [
                    'rule_id' => $rule->id,
                    'treat_as_gross_amount' => true,
                    'purpose' => 'native_gross_sweep_finalize',
                ]
            );

            $estimate = $this->fireblocksTx->estimateFee($txRequest, false);
            $networkFee = SweepFireblocksFeeEstimate::extractNetworkFee($estimate, $rule->feeLevel());
        } catch (\Throwable $e) {
            $this->logger->logDiagnostic(
                'warning',
                'native_gross_fee_estimate_failed',
                'Fee estimate failed for native gross sweep — using available-capped amount: ' . $e->getMessage(),
                $job,
                $sourceVaultId,
                $transferAssetId,
                ['amount' => $amount, 'available' => $available]
            );

            return [
                'amount' => $amount,
                'available' => $available,
                'network_fee' => null,
            ];
        }

        $net = SweepMath::sub($amount, $networkFee, 18);
        if (SweepMath::comp($networkFee, '0', 18) > 0) {
            if (SweepMath::comp($net, '0', 18) <= 0) {
                return [
                    'skip_reason' => 'native_gross_below_network_fee',
                    'available' => $available,
                    'amount' => $amount,
                    'network_fee' => $networkFee,
                ];
            }

            if (SweepMath::comp($net, $networkFee, 18) < 0) {
                return [
                    'skip_reason' => 'native_gross_amount_too_small',
                    'available' => $available,
                    'amount' => $amount,
                    'network_fee' => $networkFee,
                    'net_after_fee' => $net,
                ];
            }
        }

        return [
            'amount' => SweepMath::forTransactionAmount($amount, $available),
            'available' => SweepMath::sanitizeTransactionAmount($available),
            'network_fee' => $networkFee,
            'net_after_fee' => $net,
        ];
    }

    protected function resolveThreshold(FireblocksSweepRule $rule, array $config, string $triggerSource = 'deposit'): string
    {
        $usesScheduledThreshold = in_array($triggerSource, ['scheduled', 'manual'], true);

        if ($usesScheduledThreshold) {
            return (string) ($rule->scheduled_min_balance ?? '0');
        }

        if (SweepMath::comp((string) $rule->min_balance, '0', 18) > 0) {
            return (string) $rule->min_balance;
        }

        return (string) ($config['default_deposit_threshold'] ?? '0');
    }

    public function resolveThresholdForTrigger(FireblocksSweepRule $rule, array $config, string $triggerSource): string
    {
        return $this->resolveThreshold($rule, $config, $triggerSource);
    }

    protected function thresholdTypeForTrigger(string $triggerSource): string
    {
        return in_array($triggerSource, ['scheduled', 'manual'], true) ? 'scheduled' : 'deposit';
    }

    protected function formatSweepTransactionNote(int $ruleId, string $sourceVaultId): string
    {
        $vaultLabel = $this->balances->vaultDisplayLabel($sourceVaultId);

        return 'Automated vault sweep (rule #' . $ruleId . ', ' . $vaultLabel . ')';
    }

    public function skip(
        ?FireblocksSweepJob $job,
        FireblocksSweepRule $rule,
        string $sourceVaultId,
        string $assetId,
        string $triggerSource,
        ?string $depositTxId,
        string $reason,
        ?array $context = null
    ): array {
        if (!$job) {
            $config = SweepConfigService::tryGet() ?? [];
            $destinationVaultId = SweepDestination::resolveVaultId($rule, $config);

            $job = FireblocksSweepJob::create([
                'rule_id' => $rule->id,
                'source_vault_id' => $sourceVaultId,
                'destination_vault_id' => $destinationVaultId,
                'asset_id' => $assetId,
                'trigger_source' => $triggerSource,
                'deposit_fireblocks_tx_id' => $depositTxId,
                'status' => FireblocksSweepJob::STATUS_SKIPPED,
                'skip_reason' => SweepJobReason::normalize($reason),
            ]);
        } else {
            $job->update([
                'status' => FireblocksSweepJob::STATUS_SKIPPED,
                'skip_reason' => SweepJobReason::normalize($reason),
            ]);
        }

        $logMessage = is_array($context) && !empty($context['message'])
            ? (string) $context['message']
            : (is_array($context) && !empty($context['why'])
                ? (string) $context['why']
                : ($reason === 'in_flight' && !empty($context['blocking_job_id'])
                    ? sprintf(
                        'Blocked by in-flight sweep job #%s (%s)',
                        $context['blocking_job_id'],
                        $context['blocking_job_status'] ?? 'unknown'
                    )
                    : $reason));

        $logLevel = $reason === 'orphan_sweep_disabled' ? 'error' : 'info';

        $this->logger->log('sweep_skipped', $logMessage, $job, level: $logLevel, context: array_merge($context ?? [], [
            'skip_reason' => $reason,
        ]));

        return ['status' => FireblocksSweepJob::STATUS_SKIPPED, 'reason' => $reason, 'job_id' => $job->id];
    }
}
