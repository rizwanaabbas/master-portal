<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Support\SweepAssetMetadata;
use App\Modules\FireblocksVaultSweeping\Support\SweepFireblocksDiagnostics;
use Fireblocks\Sdk\Models\TransactionRequest;
use Tests\TestCase;

class SweepTreatAsGrossAmountTest extends TestCase
{
    public function test_should_treat_as_gross_only_for_native_account_when_enabled(): void
    {
        $rule = new FireblocksSweepRule([
            'asset_ids' => 'ETH_TEST5',
            'treat_as_gross_amount' => true,
        ]);

        $this->assertTrue(SweepAssetMetadata::isNativeAccountAsset('ETH_TEST5'));
        $this->assertTrue($rule->shouldTreatAsGrossAmount('ETH_TEST5'));

        $rule->treat_as_gross_amount = false;
        $this->assertFalse($rule->shouldTreatAsGrossAmount('ETH_TEST5'));
    }

    public function test_should_not_treat_as_gross_for_token_asset(): void
    {
        $rule = new FireblocksSweepRule([
            'asset_ids' => 'USDT_TRX_TEST4',
            'treat_as_gross_amount' => true,
        ]);

        $this->assertFalse(SweepAssetMetadata::isNativeAccountAsset('USDT_TRX_TEST4'));
        $this->assertFalse($rule->shouldTreatAsGrossAmount('USDT_TRX_TEST4'));
    }

    public function test_build_transaction_payload_includes_treat_as_gross_amount(): void
    {
        $request = new TransactionRequest();
        $request
            ->withAsset('ETH_TEST5')
            ->fromVaultAccount('158')
            ->toVaultAccount('0')
            ->withAmount('0.05')
            ->withFeeLevel('LOW');
        $request->treatAsGrossAmount = true;

        $payload = SweepFireblocksDiagnostics::buildTransactionPayload($request);

        $this->assertTrue($payload['treatAsGrossAmount']);
    }
}
