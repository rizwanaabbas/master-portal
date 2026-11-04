<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Services\SweepGasRelayStatusService;
use PHPUnit\Framework\TestCase;

class SweepGasRelayStatusServiceTest extends TestCase
{
    public function test_probe_returns_not_applicable_for_tron_usdt(): void
    {
        $service = new SweepGasRelayStatusService();

        $result = $service->probe('TRX_USDT_S2UZ');

        $this->assertSame('not_applicable', $result['status']);
        $this->assertFalse($result['supports_gas_relay']);
    }

    public function test_probe_returns_not_applicable_for_base_usdc(): void
    {
        $service = new SweepGasRelayStatusService();

        $result = $service->probe('USDC_BASECHAIN_ETH_TEST5_8SH8');

        $this->assertSame('not_applicable', $result['status']);
    }
}
