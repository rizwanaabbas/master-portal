<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Services\SweepLogService;
use App\Modules\FireblocksVaultSweeping\Services\TronGasFundingService;
use App\Modules\FireblocksVaultSweeping\Support\SweepAssetMetadata;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class TronGasFundingServiceTest extends TestCase
{
    public function test_requires_tron_gas_for_trc20_token(): void
    {
        $service = new TronGasFundingService(new SweepLogService());
        $rule = new FireblocksSweepRule();

        $this->assertTrue($service->requiresTronGasFunding($rule, 'TRX_USDT_S2UZ'));
        $this->assertFalse($service->requiresTronGasFunding($rule, 'ETH'));
    }

    public function test_resolve_funding_vault_prefers_rule_over_module_default_for_tron(): void
    {
        $service = new TronGasFundingService(new SweepLogService());
        $rule = new FireblocksSweepRule(['gas_funding_vault_id' => 'rule-station']);

        $this->assertSame('rule-station', $service->resolveFundingVault($rule, [
            'default_tron_station_vault_id' => 'module-station',
        ], 'TRX'));
    }

    public function test_resolve_funding_vault_returns_null_for_evm_gas(): void
    {
        $service = new TronGasFundingService(new SweepLogService());
        $rule = new FireblocksSweepRule(['gas_funding_vault_id' => '95']);

        $this->assertNull($service->resolveFundingVault($rule, [
            'default_tron_station_vault_id' => '95',
        ], 'ETH_TEST5'));
    }

    public function test_supports_automatic_gas_feed_only_for_tron_assets(): void
    {
        $service = new TronGasFundingService(new SweepLogService());

        $this->assertTrue($service->supportsAutomaticGasFeed('TRX'));
        $this->assertTrue($service->supportsAutomaticGasFeed('TRX_TEST'));
        $this->assertFalse($service->supportsAutomaticGasFeed('ETH_TEST5'));
        $this->assertFalse($service->supportsAutomaticGasFeed('BASECHAIN_ETH_TEST5'));
    }

    public function test_uses_evm_gas_relay_for_ethereum_usdc(): void
    {
        $service = new TronGasFundingService(new SweepLogService());

        $this->assertTrue($service->usesEvmGasRelay('USDC_ETH_TEST5_0GER'));
        $this->assertFalse($service->usesEvmGasRelay('USDC_BASECHAIN_ETH_TEST5_8SH8'));
        $this->assertFalse($service->usesEvmGasRelay('TRX_USDT_S2UZ'));
    }

    public function test_extract_network_fee_reads_low_tier(): void
    {
        $service = new TronGasFundingService(new SweepLogService());
        $method = new ReflectionMethod(TronGasFundingService::class, 'extractNetworkFee');
        $method->setAccessible(true);

        $fee = $method->invoke($service, [
            'low' => ['networkFee' => '1.25'],
            'medium' => ['networkFee' => '2.00'],
        ], 'LOW');

        $this->assertSame('1.25', $fee);
    }

    public function test_extract_network_fee_uses_rule_fee_level_tier(): void
    {
        $service = new TronGasFundingService(new SweepLogService());
        $method = new ReflectionMethod(TronGasFundingService::class, 'extractNetworkFee');
        $method->setAccessible(true);

        $fee = $method->invoke($service, [
            'low' => ['networkFee' => '1.25'],
            'medium' => ['networkFee' => '2.00'],
        ], 'MEDIUM');

        $this->assertSame('2.00', $fee);
    }

    public function test_tron_token_metadata_marks_gas_required(): void
    {
        $meta = SweepAssetMetadata::describe(null, 'TRX_USDT_S2UZ');

        $this->assertTrue($meta['gas_required']);
        $this->assertSame('TRX', $meta['gas_asset_id']);
        $this->assertStringContainsString('TRON Station', implode(' ', $meta['sweep_flow']));
    }

    public function test_identifies_gas_feed_transactions_by_external_tx_id(): void
    {
        $this->assertTrue(TronGasFundingService::isGasFeedTransaction([
            'externalTxId' => 'cib-sweep-gas-42-20260908120000',
        ]));
        $this->assertFalse(TronGasFundingService::isGasFeedTransaction([
            'externalTxId' => 'cib-sweep-deposit-abc',
        ]));
    }

    public function test_identifies_gas_feed_transactions_by_note(): void
    {
        $this->assertTrue(TronGasFundingService::isGasFeedTransaction([
            'note' => 'Gas feed (TRX_TEST) for sweep job #7',
        ]));
    }
}
