<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\Fireblocks\Models\Asset;
use App\Modules\Fireblocks\Models\FireblocksVaultAccount;
use App\Modules\FireblocksVaultSweeping\Jobs\ExecuteSweepJob;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use Illuminate\Support\Facades\Log;

class SweepEvaluationService
{
    public function __construct(
        protected SweepRuleMatcher $matcher,
        protected SweepExecutionService $execution,
        protected SweepLogService $logger,
        protected SweepBalanceService $balances,
        protected TaggedVaultScanService $taggedVaultScan,
        protected SweepScheduleService $schedule,
        protected SweepRuntimeLogger $runtime,
    ) {
    }

    public function evaluateDeposit(string $fireblocksTxId, array $webhookData, ?array $config = null): array
    {
        $config = $config ?? SweepConfigService::tryGet();
        $sourceVaultId = (string) ($webhookData['destination']['id'] ?? '');
        $assetId = (string) ($webhookData['assetId'] ?? '');
        $orphanDeposit = !empty($webhookData['_sweep_context']['orphan_deposit']);
        $ledgerCredited = !empty($webhookData['_sweep_context']['ledger_credited']);
        $ledgerAlreadyApproved = !empty($webhookData['_sweep_context']['ledger_already_approved']);
        $ledgerReady = $ledgerCredited || $orphanDeposit || $ledgerAlreadyApproved;
        $webhookTags = is_array($webhookData['destination']['tags'] ?? null)
            ? $webhookData['destination']['tags']
            : null;

        $this->logDepositWebhook('evaluation started', $fireblocksTxId, [
            'trigger' => 'deposit',
            'vault_id' => $sourceVaultId,
            'asset_id' => $assetId,
            'orphan_deposit' => $orphanDeposit,
            'ledger_credited' => $ledgerCredited,
            'ledger_already_approved' => $ledgerAlreadyApproved,
            'ledger_ready' => $ledgerReady,
            'webhook_tag_ids' => $this->extractTagIds($webhookTags),
            'configured_ci_tag_id' => trim((string) ($config['client_intermediary_tag_id'] ?? '')),
            'sweeping_active' => SweepConfigService::isActive(),
        ]);

        if (!SweepConfigService::isActive()) {
            $result = ['evaluated' => 0, 'message' => 'Sweeping inactive', 'status' => 'skipped'];

            $this->logDepositWebhook('evaluation skipped — sweeping inactive', $fireblocksTxId, $result);

            return $result;
        }

        if (!$ledgerReady) {
            $result = ['evaluated' => 0, 'message' => 'Deposit ledger not credited yet', 'status' => 'skipped'];

            $this->logDepositWebhook('evaluation skipped — ledger not credited', $fireblocksTxId, $result);

            return $result;
        }

        if ($sourceVaultId === '' || $assetId === '') {
            $result = ['evaluated' => 0, 'message' => 'Missing vault or asset', 'status' => 'skipped'];

            $this->logDepositWebhook('evaluation skipped — missing vault or asset', $fireblocksTxId, $result);

            return $result;
        }

        $rules = $this->matcher->matchingRules($sourceVaultId, $assetId, 'deposit', $config, $webhookTags);
        if ($orphanDeposit) {
            $rules = $rules->filter(fn (FireblocksSweepRule $rule) => $rule->sweepsOrphanDeposits());
        }

        if ($rules->isEmpty()) {
            $diagnostics = $this->matcher->diagnoseDepositRules(
                $sourceVaultId,
                $assetId,
                $webhookTags,
                $config,
                $orphanDeposit
            );

            $rulesIgnoringOrphanFilter = $this->matcher->matchingRules(
                $sourceVaultId,
                $assetId,
                'deposit',
                $config,
                $webhookTags
            );

            if ($orphanDeposit && $rulesIgnoringOrphanFilter->isNotEmpty()) {
                $rule = $rulesIgnoringOrphanFilter->first();
                $vaultLabel = $this->balances->vaultName($sourceVaultId);
                $skipMessage = sprintf(
                    'Orphan deposit (no client ledger match) — rule #%d "%s" has Sweep orphan deposits disabled; sweep was not submitted.',
                    $rule->id,
                    $rule->name
                );

                $skipResult = $this->execution->skip(
                    null,
                    $rule,
                    $sourceVaultId,
                    $assetId,
                    'deposit',
                    $fireblocksTxId,
                    'orphan_sweep_disabled',
                    [
                        'message' => $skipMessage,
                        'orphan_deposit' => true,
                        'deposit_fireblocks_tx_id' => $fireblocksTxId,
                        'source_vault_name' => $vaultLabel,
                        'rule_diagnostics' => $diagnostics,
                    ]
                );

                $result = [
                    'evaluated' => 0,
                    'message' => $skipMessage,
                    'status' => 'orphan_sweep_disabled',
                    'job_id' => $skipResult['job_id'] ?? null,
                    'rule_diagnostics' => $diagnostics,
                ];

                $this->logDepositWebhook('evaluation blocked — orphan sweep disabled on rule', $fireblocksTxId, $result);

                return $result;
            }

            $result = [
                'evaluated' => 0,
                'message' => $orphanDeposit
                    ? 'No matching deposit rules with orphan sweep enabled'
                    : 'No matching deposit rules',
                'status' => 'no_matching_rules',
                'rule_diagnostics' => $diagnostics,
            ];

            $this->logDepositWebhook('evaluation finished — no matching rules', $fireblocksTxId, $result);

            return $result;
        }

        $this->logDepositWebhook('matched rules — executing', $fireblocksTxId, [
            'rule_ids' => $rules->pluck('id')->all(),
            'rule_names' => $rules->pluck('name')->all(),
        ]);

        $results = [];
        foreach ($rules as $rule) {
            $ruleResult = $this->queueOrExecute($rule, $sourceVaultId, $assetId, 'deposit', $fireblocksTxId, $config);
            $results[] = array_merge(['rule_id' => $rule->id, 'rule_name' => $rule->name], $ruleResult);

            $this->logDepositWebhook('rule evaluated', $fireblocksTxId, [
                'trigger' => 'deposit',
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'status' => $ruleResult['status'] ?? 'unknown',
                'reason' => $ruleResult['reason'] ?? null,
                'job_id' => $ruleResult['job_id'] ?? null,
                'delay_minutes' => $ruleResult['delay_minutes'] ?? null,
            ]);
        }

        $result = ['evaluated' => count($results), 'results' => $results, 'status' => 'evaluated'];

        $this->logDepositWebhook('evaluation finished', $fireblocksTxId, $result);

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $tags
     * @return list<string>
     */
    protected function extractTagIds(?array $tags): array
    {
        if ($tags === null) {
            return [];
        }

        $ids = [];
        foreach ($tags as $tag) {
            foreach ($this->taggedVaultScan->extractTagIdentifiers($tag) as $id) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function logDepositWebhook(string $stage, string $fireblocksTxId, array $context = []): void
    {
        Log::info('[Sweep deposit webhook] ' . $stage, array_merge([
            'fireblocks_tx_id' => $fireblocksTxId,
        ], $context));
    }

    public function evaluateScheduled(?array $config = null, bool $ignoreSchedule = false): array
    {
        if (!SweepConfigService::isActive()) {
            $result = ['evaluated' => 0, 'submitted' => 0, 'skipped_not_due' => 0, 'message' => 'Sweeping inactive', 'results' => []];
            $this->runtime->warning(SweepRuntimeLogger::CHANNEL_SCHEDULED, 'run skipped — sweeping inactive');

            return $result;
        }

        $config = $config ?? SweepConfigService::get();
        $maxSweeps = (int) ($config['max_sweeps_per_run'] ?? 50);
        $results = [];
        $submitted = 0;
        $skippedNotDue = 0;

        $rules = FireblocksSweepRule::query()
            ->active()
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->filter(fn (FireblocksSweepRule $rule) => $rule->supportsTrigger('scheduled'));

        $this->runtime->info(SweepRuntimeLogger::CHANNEL_SCHEDULED, 'run started', [
            'ignore_schedule' => $ignoreSchedule,
            'max_sweeps_per_run' => $maxSweeps,
            'scheduled_rule_count' => $rules->count(),
            'dry_run' => $this->isDryRunContext($config),
        ]);

        foreach ($rules as $rule) {
            if ($submitted >= $maxSweeps) {
                $this->runtime->warning(SweepRuntimeLogger::CHANNEL_SCHEDULED, 'max sweeps per run reached — stopping', [
                    'max_sweeps_per_run' => $maxSweeps,
                    'submitted' => $submitted,
                ]);
                break;
            }

            if (!$ignoreSchedule && !$this->schedule->isDueForScheduledRun($rule, $config)) {
                $skippedNotDue++;
                $this->runtime->info(SweepRuntimeLogger::CHANNEL_SCHEDULED, 'rule skipped — not due', [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'schedule_interval_minutes' => $rule->schedule_interval_minutes,
                    'schedule_run_at' => $rule->schedule_run_at,
                    'schedule_last_run_at' => $rule->schedule_last_run_at?->toDateTimeString(),
                ]);
                continue;
            }

            $threshold = $this->execution->resolveThresholdForTrigger($rule, $config, 'scheduled');
            $pairs = $this->resolveCandidatePairsForRule($rule, $config, true, 'scheduled', $threshold);
            $ruleResults = [];

            $this->runtime->info(SweepRuntimeLogger::CHANNEL_SCHEDULED, 'rule evaluating', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'candidate_pairs' => count($pairs),
                'scheduled_min_balance' => $threshold,
            ]);

            if ($pairs === []) {
                $this->runtime->info(SweepRuntimeLogger::CHANNEL_SCHEDULED, 'rule has no matching vault/asset candidates', [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                ]);
            }

            foreach ($pairs as $pair) {
                if ($submitted >= $maxSweeps) {
                    break 2;
                }

                $result = $this->queueOrExecute(
                    $rule,
                    $pair['vault_id'],
                    $pair['asset_id'],
                    'scheduled',
                    null,
                    $config
                );

                $result = array_merge([
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'vault_id' => $pair['vault_id'],
                    'asset_id' => $pair['asset_id'],
                ], $result);
                $ruleResults[] = $result;
                $results[] = $result;

                $this->runtime->info(SweepRuntimeLogger::CHANNEL_SCHEDULED, 'vault evaluated', [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'vault_id' => $pair['vault_id'],
                    'asset_id' => $pair['asset_id'],
                    'status' => $result['status'] ?? 'unknown',
                    'reason' => $result['reason'] ?? $result['skip_reason'] ?? null,
                    'job_id' => $result['job_id'] ?? null,
                    'fireblocks_tx_id' => $result['fireblocks_tx_id'] ?? null,
                    'amount' => $result['amount'] ?? null,
                ]);

                if (($result['status'] ?? '') === FireblocksSweepJob::STATUS_SUBMITTED
                    || ($result['status'] ?? '') === FireblocksSweepJob::STATUS_WAITING_FOR_GAS) {
                    $submitted++;
                }
            }

            if (!$ignoreSchedule && $this->schedule->shouldAdvanceScheduleAfterRun($ruleResults, $pairs !== [])) {
                $this->schedule->markScheduledRun($rule);
                $this->runtime->info(SweepRuntimeLogger::CHANNEL_SCHEDULED, 'rule schedule marked', [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                ]);
            } elseif (!$ignoreSchedule && $ruleResults !== []) {
                $this->runtime->info(SweepRuntimeLogger::CHANNEL_SCHEDULED, 'rule schedule not advanced — will retry next cron', [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'results' => $ruleResults,
                ]);
            }
        }

        $summary = [
            'evaluated' => count($results),
            'submitted' => $submitted,
            'skipped_not_due' => $skippedNotDue,
            'results' => $results,
        ];

        $this->runtime->info(SweepRuntimeLogger::CHANNEL_SCHEDULED, 'run finished', $summary);
        $this->runtime->printResultsTable($results);
        $this->runtime->printRunSummary($summary);

        return $summary;
    }

    public function evaluateManual(
        string $sourceVaultId,
        string $assetId,
        ?int $ruleId = null,
        ?array $config = null
    ): array {
        if (!SweepConfigService::isActive()) {
            return ['success' => false, 'message' => 'Sweeping inactive'];
        }

        $rules = $ruleId
            ? FireblocksSweepRule::query()->active()->where('id', $ruleId)->get()
            : $this->matcher->matchingRules($sourceVaultId, $assetId, 'scheduled', $config);

        if ($rules->isEmpty()) {
            $message = $ruleId !== null
                ? (FireblocksSweepRule::query()->find($ruleId)?->isRunnable() === false
                    ? 'Rule is disabled or archived'
                    : 'No matching rules')
                : 'No matching rules';

            return ['success' => false, 'message' => $message];
        }

        $results = [];
        foreach ($rules as $rule) {
            $results[] = $this->queueOrExecute($rule, $sourceVaultId, $assetId, 'manual', null, $config);
        }

        return ['success' => true, 'results' => $results];
    }

    public function evaluateRule(FireblocksSweepRule $rule, ?array $config = null, bool $forceResubmit = false): array
    {
        if (!SweepConfigService::isActive()) {
            return ['success' => false, 'message' => 'Sweeping inactive'];
        }

        if (!$rule->isRunnable()) {
            return [
                'success' => false,
                'message' => 'Rule is disabled or archived — no sweeps were run.',
                'evaluated' => 0,
                'submitted' => 0,
                'skipped' => 0,
                'failed' => 0,
                'results' => [],
            ];
        }

        $config = $config ?? SweepConfigService::get();
        $maxSweeps = (int) ($config['max_sweeps_per_run'] ?? 50);
        $results = [];
        $submitted = 0;
        $skipped = 0;
        $failed = 0;

        $pairs = $this->resolveCandidatePairsForRule($rule, $config, true, 'manual');

        foreach ($pairs as $pair) {
            if ($submitted >= $maxSweeps) {
                break;
            }

            $result = $this->queueOrExecute($rule, $pair['vault_id'], $pair['asset_id'], 'manual', null, $config, $forceResubmit);
            $result['vault_id'] = $pair['vault_id'];
            $result['asset_id'] = $pair['asset_id'];
            $results[] = $result;

            if (($result['status'] ?? '') === FireblocksSweepJob::STATUS_SUBMITTED
                || ($result['status'] ?? '') === FireblocksSweepJob::STATUS_WAITING_FOR_GAS) {
                $submitted++;
            } elseif (($result['status'] ?? '') === FireblocksSweepJob::STATUS_SKIPPED) {
                $skipped++;
            } elseif (($result['status'] ?? '') === FireblocksSweepJob::STATUS_FAILED) {
                $failed++;
            }
        }

        $message = sprintf(
            'Rule run complete. Evaluated %d vault/asset pairs; %d submitted; %d skipped; %d failed.',
            count($results),
            $submitted,
            $skipped,
            $failed
        );

        $detailLines = $this->formatRunResultDetails($results);
        if ($detailLines !== []) {
            $message .= ' ' . implode(' ', $detailLines);
        }

        return [
            'success' => true,
            'message' => $message,
            'evaluated' => count($results),
            'submitted' => $submitted,
            'skipped' => $skipped,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     * @return array<int, string>
     */
    protected function formatRunResultDetails(array $results): array
    {
        $lines = [];

        foreach ($results as $result) {
            $status = (string) ($result['status'] ?? '');
            $vaultId = (string) ($result['vault_id'] ?? $result['source_vault_id'] ?? '?');
            $assetId = (string) ($result['asset_id'] ?? '?');

            if ($status === FireblocksSweepJob::STATUS_SUBMITTED) {
                continue;
            }

            $reason = (string) ($result['reason'] ?? $result['message'] ?? $status);
            $lines[] = sprintf('[%s %s: %s — %s]', $vaultId, $assetId, $status, $this->humanizeSkipReason($reason));
        }

        return $lines;
    }

    protected function humanizeSkipReason(string $reason): string
    {
        return match ($reason) {
            'dry_run' => 'dry run enabled (no Fireblocks transaction sent)',
            'duplicate_external_tx_id' => 'duplicate idempotency key (deposit replay or in-flight job)',
            'in_flight' => 'another sweep already in progress for this vault/asset',
            'below_threshold' => 'balance below dust threshold after re-check',
            'insufficient_after_reserve' => 'sweep amount computed as zero',
            'invalid_destination' => 'omnibus/destination vault not configured or same as source',
            'rule_disabled' => 'rule is disabled or archived',
            'insufficient_native_gas' => 'source vault needs native gas — add ETH/BASECHAIN ETH to source vault (TRON Station cannot fund EVM gas)',
            'zero_native_gas_balance' => 'native gas wallet exists but available balance is zero',
            'native_gas_wallet_missing' => 'enable native gas asset on source vault in Fireblocks',
            'gas_estimate_failed' => 'Fireblocks fee estimate API failed — see job logs for FB message',
            'gas_funding_vault_missing_asset' => 'gas funding vault does not have the required native gas asset enabled',
            'gas_funding_vault_insufficient' => 'gas funding vault has insufficient native gas',
            'insufficient_after_gas_feed' => 'no sweepable balance after gas feed',
            default => $reason !== '' ? $reason : 'unknown',
        };
    }

    public function resolveSourceVaultsForRulePublic(FireblocksSweepRule $rule): array
    {
        return $this->resolveSourceVaultsForRule($rule);
    }

    public function resolveAssetsForRulePublic(FireblocksSweepRule $rule): array
    {
        return $this->resolveAssetsForRule($rule);
    }

    /**
     * Vault/asset pairs to evaluate for a rule (assets must exist on vault in Fireblocks).
     *
     * @return array<int, array{vault_id: string, asset_id: string}>
     */
    public function resolveCandidatePairsForRule(
        FireblocksSweepRule $rule,
        ?array $config = null,
        bool $nonZeroBalanceOnly = true,
        string $triggerSource = 'scheduled',
        ?string $threshold = null
    ): array {
        if (!$rule->isRunnable()) {
            return [];
        }

        $config = $config ?? SweepConfigService::tryGet() ?? [];

        if ($rule->source_type === 'client_deposit_vaults') {
            $threshold = $threshold ?? $this->execution->resolveThresholdForTrigger($rule, $config, $triggerSource);

            return $this->taggedVaultScan->findCandidatesForRule(
                $rule,
                $triggerSource,
                $threshold,
                $config,
                $nonZeroBalanceOnly
            );
        }

        $vaults = $this->resolveSourceVaultsForRule($rule);
        $ruleAssetFilter = $rule->assetIdList();

        $pairs = [];
        foreach ($vaults as $vaultId) {
            $assetIds = $this->balances->listAssetIdsOnVault(
                (string) $vaultId,
                $ruleAssetFilter,
                $this->matcher,
                $nonZeroBalanceOnly
            );

            if ($assetIds === []) {
                continue;
            }

            foreach ($assetIds as $assetId) {
                if (!$this->matcher->ruleApplies($rule, (string) $vaultId, (string) $assetId, $config, null)) {
                    continue;
                }

                $pairs[] = [
                    'vault_id' => (string) $vaultId,
                    'asset_id' => (string) $assetId,
                ];
            }
        }

        return $pairs;
    }

    public function isDryRunContext(?array $config = null, ?FireblocksSweepRule $rule = null): bool
    {
        $config = $config ?? SweepConfigService::tryGet() ?? [];

        return !empty($config['dry_run_mode']) || ($rule !== null && $rule->dry_run);
    }

    protected function resolveSourceVaultsForRule(FireblocksSweepRule $rule): array
    {
        if ($rule->source_type === 'vault_id_list') {
            return $rule->sourceVaultIdList();
        }

        if ($rule->source_type === 'vault_name_prefix') {
            $prefix = strtolower(trim((string) $rule->source_vault_ids));
            if ($prefix === '') {
                return [];
            }

            return FireblocksVaultAccount::query()
                ->get(['vault_id', 'vault_title'])
                ->filter(fn ($vault) => str_starts_with(strtolower((string) $vault->vault_title), $prefix))
                ->pluck('vault_id')
                ->all();
        }

        return FireblocksVaultAccount::query()->pluck('vault_id')->all();
    }

    protected function resolveAssetsForRule(FireblocksSweepRule $rule): array
    {
        $assetIds = $rule->assetIdList();
        if ($assetIds !== []) {
            return $assetIds;
        }

        return Asset::query()
            ->whereIn('type', ['account', 'token', 'fiat'])
            ->pluck('asset_id')
            ->unique()
            ->values()
            ->all();
    }

    protected function queueOrExecute(
        FireblocksSweepRule $rule,
        string $sourceVaultId,
        string $assetId,
        string $triggerSource,
        ?string $depositTxId,
        ?array $config,
        bool $forceResubmit = false
    ): array {
        if (!$rule->isRunnable()) {
            $this->logger->log('sweep_skipped_rule_disabled', 'Sweep skipped — rule is disabled or archived', null, $sourceVaultId, $assetId, 'info', [
                'rule_id' => $rule->id,
                'trigger_source' => $triggerSource,
            ]);

            return $this->execution->skip(
                null,
                $rule,
                $sourceVaultId,
                $assetId,
                $triggerSource,
                $depositTxId,
                'rule_disabled'
            );
        }

        if (!$forceResubmit) {
            $blockingJob = $this->execution->findBlockingInFlightJob(
                $sourceVaultId,
                $assetId,
                $triggerSource,
                $depositTxId
            );

            if ($blockingJob !== null) {
                $sameDeposit = $triggerSource === 'deposit'
                    && trim((string) $depositTxId) !== ''
                    && trim((string) $blockingJob->deposit_fireblocks_tx_id) === trim((string) $depositTxId)
                    && (int) $blockingJob->rule_id === (int) $rule->id;

                if ($sameDeposit) {
                    Log::info('[Sweep deposit webhook] resuming in-flight job for same deposit', [
                        'fireblocks_tx_id' => $depositTxId,
                        'blocking_job_id' => $blockingJob->id,
                        'blocking_job_status' => $blockingJob->status,
                    ]);

                    try {
                        $recovery = app(\App\Modules\FireblocksVaultSweeping\Services\TronGasFundingService::class)
                            ->recoverGasLegForJob($blockingJob);
                        $blockingJob->refresh();

                        return array_merge([
                            'status' => $recovery['status'] ?? $blockingJob->status,
                            'job_id' => $blockingJob->id,
                            'resumed_same_deposit' => true,
                        ], $recovery);
                    } catch (\Throwable $e) {
                        Log::warning('[Sweep deposit webhook] same-deposit recovery failed', [
                            'fireblocks_tx_id' => $depositTxId,
                            'blocking_job_id' => $blockingJob->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $this->logger->log('sweep_skipped_in_flight', 'Sweep already in flight', null, $sourceVaultId, $assetId, 'info', [
                    'rule_id' => $rule->id,
                    'trigger_source' => $triggerSource,
                    'blocking_job_id' => $blockingJob->id,
                    'blocking_job_status' => $blockingJob->status,
                    'blocking_job_trigger' => $blockingJob->trigger_source,
                    'blocking_deposit_tx_id' => $blockingJob->deposit_fireblocks_tx_id,
                ]);

                Log::info('[Sweep deposit webhook] skipped — another sweep in flight', [
                    'fireblocks_tx_id' => $depositTxId,
                    'trigger' => $triggerSource,
                    'vault_id' => $sourceVaultId,
                    'asset_id' => $assetId,
                    'blocking_job_id' => $blockingJob->id,
                    'blocking_job_status' => $blockingJob->status,
                ]);

                return $this->execution->skip(
                    null,
                    $rule,
                    $sourceVaultId,
                    $assetId,
                    $triggerSource,
                    $depositTxId,
                    'in_flight',
                    [
                        'blocking_job_id' => $blockingJob->id,
                        'blocking_job_status' => $blockingJob->status,
                        'blocking_job_trigger' => $blockingJob->trigger_source,
                    ]
                );
            }
        }

        $delayMinutes = $triggerSource === 'deposit' ? (int) $rule->deposit_delay_minutes : 0;

        if ($delayMinutes > 0 && $triggerSource === 'deposit') {
            $queuedJob = $this->execution->createQueuedJob(
                $rule,
                $sourceVaultId,
                $assetId,
                $triggerSource,
                $depositTxId,
                $config
            );

            ExecuteSweepJob::dispatch(
                $rule->id,
                $sourceVaultId,
                $assetId,
                $triggerSource,
                $depositTxId,
                $queuedJob->id
            )->delay(now()->addMinutes($delayMinutes));

            $this->logger->log('sweep_queued', 'Sweep queued with delay', $queuedJob, $sourceVaultId, $assetId, 'info', [
                'rule_id' => $rule->id,
                'delay_minutes' => $delayMinutes,
                'deposit_fireblocks_tx_id' => $depositTxId,
            ]);

            return [
                'status' => FireblocksSweepJob::STATUS_QUEUED,
                'delay_minutes' => $delayMinutes,
                'job_id' => $queuedJob->id,
            ];
        }

        return $this->execution->execute($rule, $sourceVaultId, $assetId, $triggerSource, $depositTxId, $config);
    }
}
