<?php

namespace Tests\Unit;

use App\Models\FireblocksTransaction;
use PHPUnit\Framework\TestCase;

class FireblocksTransactionCustodyOnlyTest extends TestCase
{
    public function test_identifies_custody_only_deposits_without_ledger(): void
    {
        $tx = new FireblocksTransaction([
            'note' => FireblocksTransaction::CUSTODY_ONLY_NO_LEDGER_NOTE,
        ]);

        $this->assertTrue(FireblocksTransaction::isCustodyOnlyWithoutLedger($tx));
    }

    public function test_ledger_linked_deposits_are_not_custody_only(): void
    {
        $tx = new FireblocksTransaction(['note' => 'Incoming client deposit']);

        $this->assertFalse(FireblocksTransaction::isCustodyOnlyWithoutLedger($tx));
        $this->assertFalse(FireblocksTransaction::isCustodyOnlyWithoutLedger(null));
    }

    public function test_tron_station_gas_feed_is_custody_only(): void
    {
        $tx = new FireblocksTransaction([
            'note' => FireblocksTransaction::CUSTODY_ONLY_SWEEP_GAS_FEED_NOTE,
        ]);

        $this->assertTrue(FireblocksTransaction::isCustodyOnlyWithoutLedger($tx));
    }
}
