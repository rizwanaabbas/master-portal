<?php

namespace App\Modules\FireblocksVaultSweeping\Console\Commands;

use App\Modules\FireblocksVaultSweeping\Services\SweepEvaluationService;
use App\Modules\FireblocksVaultSweeping\Services\SweepRuntimeLogger;
use Illuminate\Console\Command;

class RunFireblocksSweeps extends Command
{
    protected $signature = 'fireblocks:run-sweeps
                            {--force : Ignore per-rule schedule due checks}';

    protected $description = 'Scan client deposit vaults and submit scheduled vault sweeps';

    public function handle(
        SweepEvaluationService $evaluation,
        SweepRuntimeLogger $runtime
    ): int {
        $runtime->bindOutput($this->output);

        try {
            $this->line('');
            $this->info('Starting Fireblocks scheduled vault sweep run…');
            $this->line('Logs: storage/logs/laravel.log (prefix [Sweep scheduled cron])');
            $this->line('');

            $result = $evaluation->evaluateScheduled(null, (bool) $this->option('force'));

            if (($result['evaluated'] ?? 0) === 0 && !empty($result['message'])) {
                $this->warn((string) $result['message']);

                return self::SUCCESS;
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $runtime->error(SweepRuntimeLogger::CHANNEL_SCHEDULED, 'run failed — ' . $e->getMessage(), [
                'exception_class' => $e::class,
            ]);

            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            $runtime->clearOutput();
        }
    }
}
