<?php

namespace Tests\Unit;

use App\Helpers\FingateHelper;
use PHPUnit\Framework\TestCase;

class FingateCardStatusMapTest extends TestCase
{
    public function test_approved_transaction_waiting_for_settlement_stays_successful(): void
    {
        $this->assertSame('Success', FingateHelper::mapTransactionStatus('S'));
        $this->assertSame('UnSettled', FingateHelper::mapSettlementStatus('N'));
    }

    public function test_provider_statuses_map_to_internal_labels(): void
    {
        $this->assertSame('Declined', FingateHelper::mapTransactionStatus('D'));
        $this->assertSame('Expired', FingateHelper::mapTransactionStatus('E'));
        $this->assertSame('Reversed', FingateHelper::mapTransactionStatus('R'));
        $this->assertSame('Declined', FingateHelper::mapTransactionStatus(null));

        $this->assertSame('Settled', FingateHelper::mapSettlementStatus('Y'));
        $this->assertSame('Declined', FingateHelper::mapSettlementStatus('D'));
        $this->assertSame('Reversed', FingateHelper::mapSettlementStatus('R'));
        $this->assertSame('UnSettled', FingateHelper::mapSettlementStatus(null));
    }
}
