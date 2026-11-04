<?php

namespace Tests\Unit;

use App\Modules\FireblocksTransactionsMonitoring\Support\PoisoningDepositScope;
use PHPUnit\Framework\TestCase;

class PoisoningDepositScopeTest extends TestCase
{
    public function test_resolve_amount_limits_uses_default_when_config_missing(): void
    {
        $limits = PoisoningDepositScope::resolveAmountLimits(null);

        $this->assertSame('1', $limits['default']);
        $this->assertSame([], $limits['by_asset']);
    }

    public function test_resolve_amount_limits_parses_asset_overrides(): void
    {
        $limits = PoisoningDepositScope::resolveAmountLimits([
            'poisoning_deposit_max_amount' => '2.5',
            'poisoning_deposit_asset_max_amounts' => 'ETH:0.00005, BTC:0.00001 , bad, USDC_ETH:1',
        ]);

        $this->assertSame('2.5', $limits['default']);
        $this->assertSame('0.00005', $limits['by_asset']['ETH']);
        $this->assertSame('0.00001', $limits['by_asset']['BTC']);
        $this->assertSame('1', $limits['by_asset']['USDC_ETH']);
        $this->assertArrayNotHasKey('bad', $limits['by_asset']);
    }

    public function test_invalid_default_falls_back(): void
    {
        $limits = PoisoningDepositScope::resolveAmountLimits([
            'poisoning_deposit_max_amount' => '-1',
        ]);

        $this->assertSame(PoisoningDepositScope::DEFAULT_MAX_AMOUNT, $limits['default']);
    }
}
