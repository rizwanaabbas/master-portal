<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Support\SweepJobReason;
use App\Modules\FireblocksVaultSweeping\Support\SweepLocalTransactionRecorder;
use PHPUnit\Framework\TestCase;

class SweepLocalTransactionRecorderTest extends TestCase
{
    public function test_resolve_destination_address_prefers_api_destination_id(): void
    {
        $transaction = (object) [
            'destinationAddress' => null,
            'destination' => (object) ['id' => '95', 'type' => 'VAULT_ACCOUNT'],
        ];

        $this->assertSame('95', SweepLocalTransactionRecorder::resolveDestinationAddress($transaction, '0'));
    }

    public function test_resolve_destination_address_falls_back_to_vault_id(): void
    {
        $transaction = (object) [
            'destinationAddress' => null,
            'destination' => null,
        ];

        $this->assertSame('42', SweepLocalTransactionRecorder::resolveDestinationAddress($transaction, '42'));
    }

    public function test_normalize_skip_reason_truncates_long_messages(): void
    {
        $long = str_repeat('x', 1200);
        $normalized = SweepJobReason::normalize($long, 1000);

        $this->assertNotNull($normalized);
        $this->assertLessThanOrEqual(1000, strlen($normalized));
        $this->assertStringEndsWith('...', $normalized);
    }
}
