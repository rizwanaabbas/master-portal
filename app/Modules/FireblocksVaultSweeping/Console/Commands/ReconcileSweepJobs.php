<?php

namespace App\Modules\FireblocksVaultSweeping\Console\Commands;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Services\SweepJobReconciliationService;
use App\Modules\FireblocksVaultSweeping\Services\SweepRuntimeLogger;
use Illuminate\Console\Command;

class ReconcileSweepJobs extends Command
{
    protected $signature = 'fireblocks:sweep-reconcile
                            {--vault= : Limit to source vault id}
                            {--asset= : Limit to asset id}
                            {--job= : Reconcile a single sweep job id}';

    protected $description = 'Poll Fireblocks for in-flight or failed (no token tx) sweep jobs and resume gas/token legs';

    public function handle(
        SweepJobReconciliationService $reconciliation,
        SweepRuntimeLogger $runtime
    ): int {
        $runtime->bindOutput($this->output);

        try {
            $vaultFilter = trim((string) $this->option('vault'));
            $assetFilter = trim((string) $this->option('asset'));
            $jobId = trim((string) $this->option('job'));

            $runtime->info(SweepRuntimeLogger::CHANNEL_RECONCILE, 'run started', [
                'vault' => $vaultFilter !== '' ? $vaultFilter : null,
                'asset' => $assetFilter !== '' ? $assetFilter : null,
                'job_id' => $jobId !== '' ? $jobId : null,
            ]);

            if ($jobId !== '') {
                $job = FireblocksSweepJob::query()->find($jobId);
                if (!$job) {
                    $this->error('Sweep job #' . $jobId . ' not found.');

                    return self::FAILURE;
                }

                $before = $job->status;
                $reconciliation->reconcileJob($job);
                $job->refresh();

                $this->info(sprintf(
                    'Job #%d: %s → %s',
                    $job->id,
                    $before,
                    $job->status
                ));

                return self::SUCCESS;
            }

            $query = FireblocksSweepJob::query()
                ->whereIn('status', FireblocksSweepJob::statusesEligibleForReconcile())
                ->where(function ($q) {
                    $q->whereIn('status', FireblocksSweepJob::IN_FLIGHT_STATUSES)
                        ->orWhere(function ($q2) {
                            $q2->where('status', FireblocksSweepJob::STATUS_FAILED)
                                ->where(function ($q3) {
                                    $q3->whereNull('fireblocks_tx_id')
                                        ->orWhere('fireblocks_tx_id', '');
                                });
                        });
                })
                ->orderByDesc('id');

            if ($vaultFilter !== '') {
                $query->where('source_vault_id', $vaultFilter);
            }

            if ($assetFilter !== '') {
                $query->where('asset_id', $assetFilter);
            }

            $pairs = $query
                ->get(['source_vault_id', 'asset_id'])
                ->unique(fn ($row) => $row->source_vault_id . '|' . $row->asset_id);

            if ($pairs->isEmpty()) {
                $runtime->info(SweepRuntimeLogger::CHANNEL_RECONCILE, 'no sweep jobs to reconcile');
                $this->warn('No pending, in-flight, or failed (open token leg) sweep jobs matched your filters.');
                $this->line('Tip: use --job=<id> for one job, or check status/skip_reason on fireblocks_sweep_jobs.');

                return self::SUCCESS;
            }

            $total = 0;
            foreach ($pairs as $pair) {
                $count = $reconciliation->reconcileVaultAsset(
                    (string) $pair->source_vault_id,
                    (string) $pair->asset_id
                );
                $total += $count;

                $runtime->info(SweepRuntimeLogger::CHANNEL_RECONCILE, 'vault/asset reconciled', [
                    'vault_id' => $pair->source_vault_id,
                    'asset_id' => $pair->asset_id,
                    'jobs_updated' => $count,
                ]);

                $this->line(sprintf(
                    'Vault %s / %s — reconciled %d job(s)',
                    $pair->source_vault_id,
                    $pair->asset_id,
                    $count
                ));
            }

            $runtime->info(SweepRuntimeLogger::CHANNEL_RECONCILE, 'run finished', [
                'pairs' => $pairs->count(),
                'jobs_updated' => $total,
            ]);

            $this->info("Done. Reconciled {$total} job(s) across {$pairs->count()} vault/asset pair(s).");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $runtime->error(SweepRuntimeLogger::CHANNEL_RECONCILE, 'run failed — ' . $e->getMessage(), [
                'exception_class' => $e::class,
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            $runtime->clearOutput();
        }
    }
}
