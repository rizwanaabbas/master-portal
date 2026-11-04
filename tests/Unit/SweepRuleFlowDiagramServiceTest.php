<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Services\SweepRuleFlowDiagramService;
use PHPUnit\Framework\TestCase;

class SweepRuleFlowDiagramServiceTest extends TestCase
{
    public function test_builds_mermaid_and_lanes_for_deposit_rule(): void
    {
        $rule = new FireblocksSweepRule([
            'name' => 'USDC test rule',
            'enabled' => true,
            'priority' => 10,
            'source_type' => 'client_deposit_vaults',
            'destination_vault_id' => '',
            'asset_ids' => 'USDC_ETH_TEST5_0GER',
            'target_asset_id' => 'USDC_ETH_TEST5_0GER',
            'trigger_modes' => 'deposit,scheduled',
            'min_balance' => 1,
            'scheduled_min_balance' => 0,
            'schedule_interval_minutes' => 240,
            'deposit_delay_minutes' => 0,
            'sweep_orphan_deposits' => true,
            'use_gasless' => true,
            'fee_level' => 'LOW',
            'sweep_amount_source' => 'available',
            'dry_run' => false,
        ]);
        $rule->forceFill(['id' => 6]);

        $service = new SweepRuleFlowDiagramService();
        $diagram = $service->build(
            $rule,
            [
                'default_omnibus_vault_id' => '0',
                'client_intermediary_tag_id' => 'bc526b1a-670f-44f3-b71b-110573994f5f',
            ],
            'Every 4 hours'
        );

        $this->assertCount(6, $diagram['lanes']);
        $this->assertSame('#6', $diagram['stats'][0]['value']);
        $this->assertSame('Deposit + Schedule', $diagram['stats'][3]['value']);
        $this->assertSame('Orphan deposits', $diagram['stats'][4]['label']);
        $this->assertSame('Yes', $diagram['stats'][4]['value']);
        $sourceLane = collect($diagram['lanes'])->firstWhere('key', 'source');
        $this->assertStringContainsString('bc526b1a-670f-44f3-b71b-110573994f5f', $sourceLane['nodes'][0]['detail']);
        $this->assertStringNotContainsString('client-intermediary', $sourceLane['nodes'][0]['detail']);
        $this->assertArrayNotHasKey('mermaid', $diagram);
    }

    public function test_includes_dry_run_in_checks_lane(): void
    {
        $rule = new FireblocksSweepRule([
            'id' => 1,
            'enabled' => true,
            'priority' => 100,
            'source_type' => 'vault_id_list',
            'source_vault_ids' => '115',
            'asset_ids' => 'ETH_TEST5',
            'trigger_modes' => 'deposit',
            'dry_run' => true,
        ]);

        $diagram = (new SweepRuleFlowDiagramService())->build($rule, [], 'Every 5 min');
        $checksLane = collect($diagram['lanes'])->firstWhere('key', 'checks');

        $this->assertNotNull($checksLane);
        $labels = array_column($checksLane['nodes'], 'label');
        $this->assertContains('Dry run', $labels);
        $gasLane = collect($diagram['lanes'])->firstWhere('key', 'gas');
        $this->assertNotNull($gasLane);
    }
}
