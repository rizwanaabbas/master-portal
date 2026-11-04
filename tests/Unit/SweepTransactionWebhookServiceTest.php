<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Services\SweepLogService;
use App\Modules\FireblocksVaultSweeping\Services\SweepTransactionWebhookService;
use Tests\TestCase;

class SweepTransactionWebhookServiceTest extends TestCase
{
    public function test_maps_completed_webhook_to_job_completed(): void
    {
        $job = FireblocksSweepJob::create([
            'rule_id' => null,
            'source_vault_id' => '115',
            'destination_vault_id' => '95',
            'asset_id' => 'USDC_ETH_TEST5_0GER',
            'trigger_source' => 'manual',
            'status' => FireblocksSweepJob::STATUS_SUBMITTED,
            'fireblocks_tx_id' => 'fb-tx-complete-1',
            'external_tx_id' => 'cib-sweep-test-complete',
        ]);

        $service = new SweepTransactionWebhookService(new SweepLogService());
        $handled = $service->handleTransactionWebhook('fb-tx-complete-1', 'COMPLETED', 'CONFIRMED');

        $this->assertTrue($handled);
        $job->refresh();
        $this->assertSame(FireblocksSweepJob::STATUS_COMPLETED, $job->status);
        $this->assertSame('COMPLETED', $job->fireblocks_status);
        $this->assertNotNull($job->completed_at);
        $this->assertNotNull($job->fireblocks_status_updated_at);
    }

    public function test_maps_failed_webhook_to_job_failed(): void
    {
        $job = FireblocksSweepJob::create([
            'rule_id' => null,
            'source_vault_id' => '115',
            'destination_vault_id' => '95',
            'asset_id' => 'USDC_ETH_TEST5_0GER',
            'trigger_source' => 'manual',
            'status' => FireblocksSweepJob::STATUS_SUBMITTED,
            'fireblocks_tx_id' => 'fb-tx-failed-1',
            'external_tx_id' => 'cib-sweep-test-failed',
        ]);

        $service = new SweepTransactionWebhookService(new SweepLogService());
        $handled = $service->handleTransactionWebhook('fb-tx-failed-1', 'FAILED', 'INSUFFICIENT_FUNDS');

        $this->assertTrue($handled);
        $job->refresh();
        $this->assertSame(FireblocksSweepJob::STATUS_FAILED, $job->status);
        $this->assertSame('FAILED', $job->fireblocks_status);
        $this->assertNotNull($job->failed_at);
    }

    public function test_logs_intermediate_webhook_without_terminal_status(): void
    {
        $job = FireblocksSweepJob::create([
            'rule_id' => null,
            'source_vault_id' => '115',
            'destination_vault_id' => '95',
            'asset_id' => 'USDC_ETH_TEST5_0GER',
            'trigger_source' => 'scheduled',
            'status' => FireblocksSweepJob::STATUS_SUBMITTED,
            'fireblocks_tx_id' => 'fb-tx-pending-1',
            'external_tx_id' => 'cib-sweep-test-pending',
        ]);

        $service = new SweepTransactionWebhookService(new SweepLogService());
        $handled = $service->handleTransactionWebhook('fb-tx-pending-1', 'PENDING_AML_SCREENING');

        $this->assertTrue($handled);
        $job->refresh();
        $this->assertSame(FireblocksSweepJob::STATUS_SUBMITTED, $job->status);
        $this->assertSame('PENDING_AML_SCREENING', $job->fireblocks_status);
        $this->assertTrue($job->logs()->where('event', 'webhook_status_update')->exists());
    }
}
