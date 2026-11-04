<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Services\SweepExecutionService;
use App\Modules\FireblocksVaultSweeping\Services\SweepLogService;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class SweepInFlightGuardTest extends TestCase
{
    public function test_deposit_sweep_not_blocked_by_queued_job_for_different_deposit_tx(): void
    {
        FireblocksSweepJob::create([
            'source_vault_id' => '115',
            'destination_vault_id' => '0',
            'asset_id' => 'USDC_ETH_TEST5_0GER',
            'trigger_source' => 'deposit',
            'deposit_fireblocks_tx_id' => 'old-deposit-tx',
            'status' => FireblocksSweepJob::STATUS_QUEUED,
            'external_tx_id' => 'cib-sweep-test-old-dep',
        ]);

        $service = new SweepExecutionService(new SweepLogService());

        $this->assertNull($service->findBlockingInFlightJob(
            '115',
            'USDC_ETH_TEST5_0GER',
            'deposit',
            'new-deposit-tx'
        ));
    }

    public function test_deposit_sweep_blocked_by_same_deposit_tx_pending_job(): void
    {
        $job = FireblocksSweepJob::create([
            'source_vault_id' => '115',
            'destination_vault_id' => '0',
            'asset_id' => 'USDC_ETH_TEST5_0GER',
            'trigger_source' => 'deposit',
            'deposit_fireblocks_tx_id' => 'same-deposit-tx',
            'status' => FireblocksSweepJob::STATUS_PENDING,
            'external_tx_id' => 'cib-sweep-test-same-dep',
        ]);

        $service = new SweepExecutionService(new SweepLogService());
        $blocking = $service->findBlockingInFlightJob(
            '115',
            'USDC_ETH_TEST5_0GER',
            'deposit',
            'same-deposit-tx'
        );

        $this->assertNotNull($blocking);
        $this->assertSame($job->id, $blocking->id);
    }

    public function test_submitted_sweep_blocks_all_triggers_for_vault_asset(): void
    {
        $job = FireblocksSweepJob::create([
            'source_vault_id' => '115',
            'destination_vault_id' => '0',
            'asset_id' => 'USDC_ETH_TEST5_0GER',
            'trigger_source' => 'scheduled',
            'status' => FireblocksSweepJob::STATUS_SUBMITTED,
            'fireblocks_tx_id' => 'fb-active-1',
            'external_tx_id' => 'cib-sweep-test-submitted',
        ]);

        $service = new SweepExecutionService(new SweepLogService());
        $blocking = $service->findBlockingInFlightJob(
            '115',
            'USDC_ETH_TEST5_0GER',
            'deposit',
            'brand-new-deposit-tx'
        );

        $this->assertNotNull($blocking);
        $this->assertSame($job->id, $blocking->id);
    }

    public function test_stale_pending_job_is_released_and_does_not_block(): void
    {
        Date::setTestNow('2026-09-08 12:00:00');

        $stale = FireblocksSweepJob::create([
            'source_vault_id' => '115',
            'destination_vault_id' => '0',
            'asset_id' => 'USDC_ETH_TEST5_0GER',
            'trigger_source' => 'deposit',
            'deposit_fireblocks_tx_id' => 'stale-dep',
            'status' => FireblocksSweepJob::STATUS_PENDING,
            'external_tx_id' => 'cib-sweep-test-stale',
        ]);
        $stale->forceFill(['updated_at' => now()->subMinutes(30)])->save();

        $service = new SweepExecutionService(new SweepLogService());

        $this->assertNull($service->findBlockingInFlightJob(
            '115',
            'USDC_ETH_TEST5_0GER',
            'deposit',
            'stale-dep'
        ));

        $stale->refresh();
        $this->assertSame(FireblocksSweepJob::STATUS_FAILED, $stale->status);
        $this->assertSame('stale_in_flight', $stale->skip_reason);

        Date::setTestNow();
    }
}
