<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Services\SweepExecutionService;
use App\Modules\FireblocksVaultSweeping\Support\SweepFireblocksFeeEstimate;
use App\Modules\FireblocksVaultSweeping\Support\SweepMath;
use Tests\TestCase;

class SweepNativeGrossAmountTest extends TestCase
{
    public function test_extract_network_fee_prefers_fee_level(): void
    {
        $estimate = [
            'low' => ['networkFee' => '0.0001'],
            'medium' => ['networkFee' => '0.0002'],
        ];

        $this->assertSame('0.0001', SweepFireblocksFeeEstimate::extractNetworkFee($estimate, 'LOW'));
        $this->assertSame('0.0002', SweepFireblocksFeeEstimate::extractNetworkFee($estimate, 'MEDIUM'));
    }

    public function test_resolve_sweep_cap_uses_available_for_native_gross(): void
    {
        $rule = new FireblocksSweepRule([
            'asset_ids' => 'ETH_TEST5',
            'treat_as_gross_amount' => true,
            'use_gasless' => false,
            'sweep_amount_source' => FireblocksSweepRule::SWEEP_AMOUNT_SOURCE_MAX_SPENDABLE,
        ]);

        $service = app(SweepExecutionService::class);
        $method = new \ReflectionMethod($service, 'resolveSweepCap');
        $method->setAccessible(true);

        $cap = $method->invoke(
            $service,
            '158',
            'ETH_TEST5',
            '0.048',
            $rule,
            'deposit',
            null,
            null
        );

        $this->assertSame('0.048', $cap);
    }

    public function test_finalize_native_gross_skips_when_fee_exceeds_amount(): void
    {
        $rule = new FireblocksSweepRule([
            'asset_ids' => 'ETH_TEST5',
            'treat_as_gross_amount' => true,
            'fee_level' => 'LOW',
        ]);

        $balances = $this->createMock(\App\Modules\FireblocksVaultSweeping\Services\SweepBalanceService::class);
        $balances->method('getVaultAssetBalance')->willReturn([
            'available' => '0.00000484111967325',
            'total' => '0.00000484111967325',
            'found' => true,
        ]);

        $fireblocksTx = $this->createMock(\App\Modules\FireblocksVaultSweeping\Support\SweepFireblocksTransactions::class);
        $fireblocksTx->method('estimateFee')->willReturn([
            'low' => ['networkFee' => '0.000021'],
        ]);

        $logger = $this->createMock(\App\Modules\FireblocksVaultSweeping\Services\SweepLogService::class);
        $logger->method('logFireblocksRequest');
        $logger->method('logDiagnostic');

        $service = new SweepExecutionService($logger, $balances, null, $fireblocksTx);

        $method = new \ReflectionMethod($service, 'finalizeNativeGrossSweepAmount');
        $method->setAccessible(true);

        $result = $method->invoke(
            $service,
            $rule,
            '115',
            'ETH_TEST5',
            '0',
            '0.00000484111967325',
            null
        );

        $this->assertSame('native_gross_below_network_fee', $result['skip_reason'] ?? null);
        $this->assertTrue(SweepMath::comp((string) ($result['network_fee'] ?? '0'), '0.00000484111967325', 18) > 0);
    }
}
