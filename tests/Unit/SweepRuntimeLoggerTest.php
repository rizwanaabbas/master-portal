<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Services\SweepRuntimeLogger;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class SweepRuntimeLoggerTest extends TestCase
{
    public function test_logs_to_laravel_without_console_output(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('[Sweep scheduled cron] run started', ['rules' => 2]);

        $logger = new SweepRuntimeLogger();
        $logger->info(SweepRuntimeLogger::CHANNEL_SCHEDULED, 'run started', ['rules' => 2]);
    }

    public function test_writes_to_console_when_output_bound(): void
    {
        Log::shouldReceive('info')->once();

        $output = new BufferedOutput();
        $logger = new SweepRuntimeLogger();
        $logger->bindOutput($output);
        $logger->info(SweepRuntimeLogger::CHANNEL_SCHEDULED, 'vault evaluated', [
            'vault_id' => '115',
            'status' => 'submitted',
        ]);

        $buffer = $output->fetch();
        $this->assertStringContainsString('[Sweep scheduled cron] vault evaluated', $buffer);
        $this->assertStringContainsString('vault_id=115', $buffer);
    }

    public function test_print_results_table_renders_rows(): void
    {
        $output = new BufferedOutput();
        $logger = new SweepRuntimeLogger();
        $logger->bindOutput($output);
        $logger->printResultsTable([
            [
                'rule_id' => 6,
                'rule_name' => 'USDC rule',
                'vault_id' => '115',
                'asset_id' => 'USDC_ETH_TEST5_0GER',
                'status' => 'completed',
                'job_id' => 35,
                'fireblocks_tx_id' => 'abc-123',
            ],
        ]);

        $buffer = $output->fetch();
        $this->assertStringContainsString('Sweep evaluation results', $buffer);
        $this->assertStringContainsString('USDC rule', $buffer);
        $this->assertStringContainsString('completed', $buffer);
    }
}
