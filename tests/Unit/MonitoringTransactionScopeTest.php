<?php

namespace Tests\Unit;

use App\Models\FireblocksTransaction;
use App\Modules\FireblocksTransactionsMonitoring\Support\MonitoringTransactionScope;
use PHPUnit\Framework\TestCase;

class MonitoringTransactionScopeTest extends TestCase
{
    public function test_detects_orphan_custody_note(): void
    {
        $record = new FireblocksTransaction([
            'note' => FireblocksTransaction::CUSTODY_ONLY_NO_LEDGER_NOTE,
        ]);

        $this->assertTrue(MonitoringTransactionScope::isOrphanCustody($record));
    }

    public function test_ledger_linked_is_not_orphan(): void
    {
        $record = new FireblocksTransaction([
            'note' => 'Client withdraw',
        ]);

        $this->assertFalse(MonitoringTransactionScope::isOrphanCustody($record));
    }

    public function test_scope_requires_ledger_account_for_poisoning(): void
    {
        $this->assertTrue(MonitoringTransactionScope::scopeRequiresLedgerAccount('poisoning'));
        $this->assertTrue(MonitoringTransactionScope::scopeRequiresLedgerAccount('ledger'));
        $this->assertFalse(MonitoringTransactionScope::scopeRequiresLedgerAccount('orphan'));
    }
}
