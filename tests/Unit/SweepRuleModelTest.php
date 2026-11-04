<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use PHPUnit\Framework\TestCase;

class SweepRuleModelTest extends TestCase
{
    public function test_trigger_mode_parsing(): void
    {
        $rule = new FireblocksSweepRule(['trigger_modes' => 'deposit, scheduled']);

        $this->assertTrue($rule->supportsTrigger('deposit'));
        $this->assertTrue($rule->supportsTrigger('scheduled'));
        $this->assertFalse($rule->supportsTrigger('manual'));
    }

    public function test_asset_id_list_parsing(): void
    {
        $rule = new FireblocksSweepRule(['asset_ids' => 'ETH, USDC']);

        $this->assertSame(['ETH', 'USDC'], $rule->assetIdList());
        $this->assertSame([], (new FireblocksSweepRule(['asset_ids' => '']))->assetIdList());
    }

    public function test_target_asset_defaults_to_source(): void
    {
        $rule = new FireblocksSweepRule(['asset_ids' => 'ETH']);

        $this->assertSame('ETH', $rule->targetAssetId());

        $rule->target_asset_id = 'ETH_TEST5';
        $this->assertSame('ETH_TEST5', $rule->targetAssetId());
    }

    public function test_is_runnable_requires_enabled_and_not_trashed(): void
    {
        $rule = new FireblocksSweepRule(['enabled' => true]);
        $this->assertTrue($rule->isRunnable());

        $rule->enabled = false;
        $this->assertFalse($rule->isRunnable());
    }

    public function test_should_use_gasless_requires_rule_flag_and_eligible_asset(): void
    {
        $rule = new FireblocksSweepRule([
            'asset_ids' => 'USDC_ETH_TEST5_0GER',
            'use_gasless' => true,
        ]);

        $this->assertTrue($rule->shouldUseGasless());

        $rule->use_gasless = false;
        $this->assertFalse($rule->shouldUseGasless());

        $rule->use_gasless = true;
        $this->assertFalse($rule->shouldUseGasless('TRX_USDT_S2UZ'));
    }

    public function test_fee_level_normalizes_to_allowed_values(): void
    {
        $rule = new FireblocksSweepRule(['fee_level' => 'medium']);
        $this->assertSame('MEDIUM', $rule->feeLevel());

        $rule->fee_level = 'invalid';
        $this->assertSame('LOW', $rule->feeLevel());
    }

    public function test_sweeps_orphan_deposits_flag(): void
    {
        $rule = new FireblocksSweepRule(['sweep_orphan_deposits' => true]);
        $this->assertTrue($rule->sweepsOrphanDeposits());

        $rule->sweep_orphan_deposits = false;
        $this->assertFalse($rule->sweepsOrphanDeposits());
    }
}
