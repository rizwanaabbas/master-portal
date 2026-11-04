<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Support\SweepFireblocksDiagnostics;
use Fireblocks\Sdk\Models\TransactionRequest;
use Tests\TestCase;

class SweepFireblocksPayloadAmountTest extends TestCase
{
    public function test_build_transaction_payload_sanitizes_amount(): void
    {
        $request = new TransactionRequest();
        $request
            ->withAsset('BNB_TEST')
            ->fromVaultAccount('161')
            ->toVaultAccount('1')
            ->withAmount('0.025000000000000001');

        $payload = SweepFireblocksDiagnostics::buildTransactionPayload($request);

        $this->assertSame('0.025', $payload['amount']);
    }
}
