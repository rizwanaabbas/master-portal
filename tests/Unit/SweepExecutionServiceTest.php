<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Services\SweepExecutionService;
use App\Modules\FireblocksVaultSweeping\Services\SweepLogService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SweepExecutionServiceTest extends TestCase
{
    public function test_build_external_tx_id_is_bounded_and_unique_per_scheduled_run(): void
    {
        $service = new SweepExecutionService(new SweepLogService());

        $scheduledId = $service->buildExternalTxId(5, '42', 'ETH', 'scheduled');
        $scheduledId2 = $service->buildExternalTxId(5, '42', 'ETH', 'scheduled');

        $this->assertStringStartsWith('fb-sweep-5-42-ETH-', $scheduledId);
        $this->assertLessThanOrEqual(255, strlen($scheduledId));
        $this->assertNotSame($scheduledId, $scheduledId2);

        $manualId = $service->buildExternalTxId(5, '42', 'ETH', 'manual');
        $this->assertNotSame($scheduledId, $manualId);
        $this->assertStringStartsWith('fb-sweep-5-42-ETH-', $manualId);

        $depositId = $service->buildExternalTxId(5, '42', 'ETH', 'deposit', 'fb-deposit-abc');
        $depositId2 = $service->buildExternalTxId(5, '42', 'ETH', 'deposit', 'fb-deposit-abc');
        $this->assertSame($depositId, $depositId2);
        $this->assertStringContainsString('-dep-', $depositId);
    }

    public function test_calculate_sweep_amount_respects_reserve_and_max_spendable(): void
    {
        $service = new SweepExecutionService(new SweepLogService());
        $method = new ReflectionMethod(SweepExecutionService::class, 'calculateSweepAmount');
        $method->setAccessible(true);

        $this->assertSame('0', $method->invoke($service, '1.0', '1.0', '2.0'));
        $this->assertSame('0.500000000000000000', $method->invoke($service, '1.0', '1.0', '0.5'));
        $this->assertSame('0.8', $method->invoke($service, '1.0', '0.8', '0.1'));
        $this->assertSame('0.900000000000000000', $method->invoke($service, '1.0', '1.5', '0.1'));
    }

    public function test_resolve_threshold_uses_deposit_threshold_for_deposit_trigger(): void
    {
        $service = new SweepExecutionService(new SweepLogService());
        $method = new ReflectionMethod(SweepExecutionService::class, 'resolveThreshold');
        $method->setAccessible(true);

        $rule = new FireblocksSweepRule([
            'min_balance' => '0.25',
            'scheduled_min_balance' => '0.01',
        ]);

        $config = [
            'default_deposit_threshold' => '0.10',
            'default_scheduled_threshold' => '0',
        ];

        $this->assertSame('0.250000000000000000', $method->invoke($service, $rule, $config, 'deposit'));
    }

    public function test_resolve_threshold_uses_scheduled_threshold_for_cron_and_manual(): void
    {
        $service = new SweepExecutionService(new SweepLogService());
        $method = new ReflectionMethod(SweepExecutionService::class, 'resolveThreshold');
        $method->setAccessible(true);

        $rule = new FireblocksSweepRule([
            'min_balance' => '500',
            'scheduled_min_balance' => '0',
        ]);

        $config = [
            'default_deposit_threshold' => '100',
            'default_scheduled_threshold' => '0.05',
        ];

        $this->assertSame('0.000000000000000000', $method->invoke($service, $rule, $config, 'scheduled'));
        $this->assertSame('0.000000000000000000', $method->invoke($service, $rule, $config, 'manual'));
    }

    public function test_resolve_threshold_falls_back_to_module_defaults_when_rule_values_zero(): void
    {
        $service = new SweepExecutionService(new SweepLogService());
        $method = new ReflectionMethod(SweepExecutionService::class, 'resolveThreshold');
        $method->setAccessible(true);

        $rule = new FireblocksSweepRule([
            'min_balance' => '0',
            'scheduled_min_balance' => '0',
        ]);

        $config = [
            'default_deposit_threshold' => '0.10',
            'default_scheduled_threshold' => '0.001',
        ];

        $this->assertSame('0.10', $method->invoke($service, $rule, $config, 'deposit'));
        $this->assertSame('0.000000000000000000', $method->invoke($service, $rule, $config, 'scheduled'));
    }

    public function test_rule_sweep_amount_source_defaults_to_available(): void
    {
        $rule = new FireblocksSweepRule();

        $this->assertSame(FireblocksSweepRule::SWEEP_AMOUNT_SOURCE_AVAILABLE, $rule->sweepAmountSource());
        $this->assertTrue($rule->usesAvailableBalanceForSweep());
    }

    public function test_rule_sweep_amount_source_available(): void
    {
        $rule = new FireblocksSweepRule([
            'sweep_amount_source' => FireblocksSweepRule::SWEEP_AMOUNT_SOURCE_AVAILABLE,
        ]);

        $this->assertSame(FireblocksSweepRule::SWEEP_AMOUNT_SOURCE_AVAILABLE, $rule->sweepAmountSource());
        $this->assertTrue($rule->usesAvailableBalanceForSweep());
        $this->assertSame('Available balance', $rule->sweepAmountSourceLabel());
    }
}
