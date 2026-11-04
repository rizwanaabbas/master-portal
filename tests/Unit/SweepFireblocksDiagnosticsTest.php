<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Support\SweepFireblocksDiagnostics;
use Fireblocks\Sdk\Exceptions\FireblocksException;
use PHPUnit\Framework\TestCase;

class SweepFireblocksDiagnosticsTest extends TestCase
{
    public function test_unsupported_asset_includes_diagnosis(): void
    {
        $e = new FireblocksException(
            'Unsupported Asset',
            400,
            '1402',
            ['message' => 'Unsupported Asset', 'code' => 1402]
        );

        $this->assertTrue(SweepFireblocksDiagnostics::isUnsupportedAssetError($e));

        $context = SweepFireblocksDiagnostics::executionFailureContext($e, [
            'source_vault_id' => '115',
            'asset_id' => 'USDC_ETH_TEST5_0GER',
            'fireblocks_step' => 'get_max_spendable_amount',
        ]);

        $this->assertSame('Unsupported Asset', $context['error']);
        $this->assertSame(400, $context['http_status']);
        $this->assertSame('1402', $context['fireblocks_code']);
        $this->assertArrayHasKey('diagnosis', $context);
        $this->assertStringContainsString('POST /v1/transactions', $context['diagnosis']['likely_causes'][0]);
        $this->assertNotEmpty($context['diagnosis']['suggested_actions']);
    }

    public function test_is_unsupported_asset_error_is_case_insensitive(): void
    {
        $this->assertTrue(SweepFireblocksDiagnostics::isUnsupportedAssetError(new \RuntimeException('unsupported asset')));
        $this->assertFalse(SweepFireblocksDiagnostics::isUnsupportedAssetError(new \RuntimeException('insufficient funds')));
    }

    public function test_format_exception_message_includes_fireblocks_response(): void
    {
        $e = new FireblocksException(
            'Could not find vault wallet for asset: ETH_TEST5, on accounts: [95]',
            400,
            '400',
            ['message' => 'Could not find vault wallet for asset: ETH_TEST5, on accounts: [95]', 'code' => 400]
        );

        $formatted = SweepFireblocksDiagnostics::formatExceptionMessage($e, 'Fireblocks gas feed create failed');

        $this->assertStringContainsString('Fireblocks gas feed create failed', $formatted);
        $this->assertStringContainsString('ETH_TEST5', $formatted);
        $this->assertStringContainsString('FB code 400', $formatted);
    }

    public function test_insufficient_native_gas_zero_balance_message(): void
    {
        $diag = SweepFireblocksDiagnostics::insufficientNativeGasDiagnostics(
            '142',
            'ETH_TEST5',
            'USDC_ETH_TEST5_0GER',
            '0',
            '0.000140856869625811',
            true
        );

        $this->assertSame('zero_native_gas_balance', $diag['failure_reason']);
        $this->assertStringContainsString('available balance is 0', $diag['why']);
        $this->assertStringContainsString('USDC_ETH_TEST5_0GER', $diag['message']);
    }

    public function test_build_transaction_payload_matches_fireblocks_shape(): void
    {
        $request = new \Fireblocks\Sdk\Models\TransactionRequest();
        $request
            ->withAsset('USDC_ETH_TEST5_0GER')
            ->fromVaultAccount('142')
            ->toVaultAccount('0')
            ->withAmount('10.5')
            ->withFeeLevel('LOW')
            ->withExternalTxId('sweep-job-1')
            ->withNote('Automated vault sweep (rule #6)');

        $payload = SweepFireblocksDiagnostics::buildTransactionPayload($request);

        $this->assertSame('USDC_ETH_TEST5_0GER', $payload['assetId']);
        $this->assertSame('VAULT_ACCOUNT', $payload['source']['type']);
        $this->assertSame('142', $payload['source']['id']);
        $this->assertSame('VAULT_ACCOUNT', $payload['destination']['type']);
        $this->assertSame('0', $payload['destination']['id']);
        $this->assertSame('10.5', $payload['amount']);
        $this->assertSame('LOW', $payload['feeLevel']);
    }

    public function test_build_transaction_payload_includes_use_gasless_when_requested(): void
    {
        $request = new \Fireblocks\Sdk\Models\TransactionRequest();
        $request
            ->withAsset('USDC_ETH_TEST5_0GER')
            ->fromVaultAccount('142')
            ->toVaultAccount('0')
            ->withAmount('10.5')
            ->withFeeLevel('LOW');

        $payload = SweepFireblocksDiagnostics::buildTransactionPayload($request, true);

        $this->assertTrue($payload['useGasless']);
    }

    public function test_build_transaction_payload_includes_treat_as_gross_when_set_on_request(): void
    {
        $request = new \Fireblocks\Sdk\Models\TransactionRequest();
        $request
            ->withAsset('ETH_TEST5')
            ->fromVaultAccount('158')
            ->toVaultAccount('0')
            ->withAmount('1')
            ->withFeeLevel('LOW');
        $request->treatAsGrossAmount = true;

        $payload = SweepFireblocksDiagnostics::buildTransactionPayload($request);

        $this->assertTrue($payload['treatAsGrossAmount']);
    }
}
