<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Support\SweepAssetMetadata;
use PHPUnit\Framework\TestCase;

class SweepAssetMetadataTest extends TestCase
{
    public function test_tron_token_requires_trx_gas(): void
    {
        $meta = SweepAssetMetadata::describe(null, 'TRX_USDT_S2UZ');

        $this->assertTrue($meta['gas_required']);
        $this->assertSame('TRX', $meta['gas_asset_id']);
        $this->assertNotEmpty($meta['sweep_flow']);
        $this->assertSame('TRX_USDT_S2UZ', $meta['default_target_asset_id']);
    }

    public function test_native_eth_does_not_require_separate_gas_asset_flag(): void
    {
        $meta = SweepAssetMetadata::describe(null, 'ETH');

        $this->assertFalse($meta['gas_required']);
        $this->assertSame('ETH', $meta['gas_asset_id']);
    }

    public function test_testnet_tron_uses_trx_test_gas_asset(): void
    {
        $meta = SweepAssetMetadata::describe(null, 'TRX_USDT_TEST');

        $this->assertTrue($meta['gas_required']);
        $this->assertSame('TRX_TEST', $meta['gas_asset_id']);
    }

    public function test_base_usdc_uses_basechain_native_gas_asset(): void
    {
        $meta = SweepAssetMetadata::describe(null, 'USDC_BASECHAIN_ETH_TEST5_8SH8');

        $this->assertTrue($meta['gas_required']);
        $this->assertSame('BASECHAIN_ETH_TEST5', $meta['gas_asset_id']);
    }

    public function test_ethereum_usdc_uses_gas_relay_not_native_preflight(): void
    {
        $meta = SweepAssetMetadata::describe(null, 'USDC_ETH_TEST5_0GER');

        $this->assertTrue($meta['gas_required']);
        $this->assertTrue($meta['uses_gas_relay']);
        $this->assertSame('gas_relay', $meta['gas_funding_mode']);
        $this->assertSame('ETH_TEST5', $meta['gas_asset_id']);
        $this->assertStringContainsString('useGasless', $meta['gas_note']);
    }

    public function test_ethereum_usdt_uses_gas_relay(): void
    {
        $meta = SweepAssetMetadata::describe(null, 'USDT_ETH_TEST5');

        $this->assertTrue($meta['uses_gas_relay']);
        $this->assertSame('gas_relay', $meta['gas_funding_mode']);
    }

    public function test_usdc_with_ethereum_network_code_uses_gas_relay(): void
    {
        $this->assertTrue(
            SweepAssetMetadata::usesEvmGasRelay('USDC', 'ETH', 'Ethereum')
        );
    }
}
