<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Services\SweepExecutionService;
use App\Modules\FireblocksVaultSweeping\Services\SweepLogService;
use App\Modules\FireblocksVaultSweeping\Services\TronGasFundingService;
use Mockery;
use Tests\TestCase;

class TronGasFundingWebhookTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_gas_feed_webhook_completed_resumes_token_sweep_inline(): void
    {
        $rule = FireblocksSweepRule::create([
            'name' => 'Test TRX rule',
            'enabled' => true,
            'priority' => 1,
            'source_type' => 'client_deposit_vaults',
            'destination_vault_id' => '95',
            'asset_ids' => 'USDT_TRX_TEST4',
            'trigger_modes' => 'deposit',
            'min_balance' => 0,
        ]);

        $job = FireblocksSweepJob::create([
            'rule_id' => $rule->id,
            'source_vault_id' => '200',
            'destination_vault_id' => '95',
            'asset_id' => 'USDT_TRX_TEST4',
            'trigger_source' => 'deposit',
            'status' => FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
            'gas_feed_fireblocks_tx_id' => 'gas-tx-1-' . uniqid(),
            'planned_sweep_amount' => '100.5',
            'external_tx_id' => 'cib-sweep-test-gas-resume-' . uniqid(),
        ]);

        $execution = Mockery::mock(SweepExecutionService::class);
        $execution->shouldReceive('resumeAfterGasFeed')
            ->once()
            ->with(Mockery::on(fn ($j) => $j->id === $job->id), Mockery::on(fn ($r) => $r->id === $rule->id))
            ->andReturn(['status' => 'submitted', 'fireblocks_tx_id' => 'token-tx-1']);

        $this->app->instance(SweepExecutionService::class, $execution);

        $service = new TronGasFundingService(new SweepLogService());
        $gasTxId = $job->gas_feed_fireblocks_tx_id;
        $handled = $service->handleFeedWebhook($gasTxId, 'COMPLETED', ['subStatus' => 'CONFIRMED']);

        $this->assertTrue($handled);
        $job->refresh();
        $this->assertSame(FireblocksSweepJob::STATUS_GAS_FUNDED, $job->status);
    }

    public function test_gas_feed_webhook_retries_when_job_stuck_gas_funded(): void
    {
        $rule = FireblocksSweepRule::create([
            'name' => 'Retry gas rule',
            'enabled' => true,
            'priority' => 1,
            'source_type' => 'client_deposit_vaults',
            'destination_vault_id' => '95',
            'asset_ids' => 'USDT_TRX_TEST4',
            'trigger_modes' => 'deposit',
            'min_balance' => 0,
        ]);

        $job = FireblocksSweepJob::create([
            'rule_id' => $rule->id,
            'source_vault_id' => '200',
            'destination_vault_id' => '95',
            'asset_id' => 'USDT_TRX_TEST4',
            'trigger_source' => 'manual',
            'status' => FireblocksSweepJob::STATUS_GAS_FUNDED,
            'gas_feed_fireblocks_tx_id' => 'gas-tx-2-' . uniqid(),
            'planned_sweep_amount' => '50',
            'external_tx_id' => 'cib-sweep-test-gas-resume-2-' . uniqid(),
            'fireblocks_tx_id' => null,
        ]);

        $execution = Mockery::mock(SweepExecutionService::class);
        $execution->shouldReceive('resumeAfterGasFeed')->once()->andReturn(['status' => 'submitted']);

        $this->app->instance(SweepExecutionService::class, $execution);

        $service = new TronGasFundingService(new SweepLogService());
        $handled = $service->handleFeedWebhook($job->gas_feed_fireblocks_tx_id, 'COMPLETED', []);

        $this->assertTrue($handled);
    }

    public function test_gas_feed_webhook_does_not_complete_while_lifecycle_still_queued(): void
    {
        $rule = FireblocksSweepRule::create([
            'name' => 'Queued gas rule',
            'enabled' => true,
            'priority' => 1,
            'source_type' => 'client_deposit_vaults',
            'destination_vault_id' => '95',
            'asset_ids' => 'USDT_TRX_TEST4',
            'trigger_modes' => 'deposit',
            'min_balance' => 0,
        ]);

        $gasTxId = 'gas-tx-queued-' . uniqid();
        $job = FireblocksSweepJob::create([
            'rule_id' => $rule->id,
            'source_vault_id' => '158',
            'destination_vault_id' => '0',
            'asset_id' => 'USDT_TRX_TEST4',
            'trigger_source' => 'deposit',
            'status' => FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
            'gas_feed_fireblocks_tx_id' => $gasTxId,
            'planned_sweep_amount' => '10',
            'external_tx_id' => 'cib-sweep-test-queued-' . uniqid(),
        ]);

        $execution = Mockery::mock(SweepExecutionService::class);
        $execution->shouldNotReceive('resumeAfterGasFeed');
        $this->app->instance(SweepExecutionService::class, $execution);

        $service = new TronGasFundingService(new SweepLogService());
        $handled = $service->handleFeedWebhook($gasTxId, 'Completed', [
            'status' => 'QUEUED',
            'externalTxId' => 'cib-sweep-gas-' . $job->id . '-test',
            'complianceResults' => ['status' => 'Completed'],
        ]);

        $this->assertFalse($handled);
        $job->refresh();
        $this->assertSame(FireblocksSweepJob::STATUS_WAITING_FOR_GAS, $job->status);
    }
}
