<?php

namespace Tests\Unit;

use App\Modules\FireblocksTransactionsMonitoring\Support\MonitoringConfigValidator;
use Tests\TestCase;

class MonitoringConfigValidatorTest extends TestCase
{
    public function test_non_retriable_removes_overlap_from_retriable(): void
    {
        $result = MonitoringConfigValidator::sanitize([
            'retriable_substatuses' => ['FAIL_ON_LOW_FEE', 'INSUFFICIENT_FUNDS'],
            'non_retriable_substatuses' => ['INSUFFICIENT_FUNDS', 'BLOCKED_BY_POLICY'],
            'boost_eligible_primary_statuses' => ['CONFIRMING'],
            'drop_eligible_primary_statuses' => ['CONFIRMING'],
            'resubmit_eligible_primary_statuses' => ['FAILED'],
        ]);

        $this->assertSame(['FAIL_ON_LOW_FEE'], $result['config']['retriable_substatuses']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_warns_when_eligibility_list_contains_non_retriable_code(): void
    {
        $result = MonitoringConfigValidator::sanitize([
            'retriable_substatuses' => [],
            'non_retriable_substatuses' => ['INSUFFICIENT_FUNDS'],
            'boost_eligible_primary_statuses' => ['CONFIRMING', 'INSUFFICIENT_FUNDS'],
            'drop_eligible_primary_statuses' => ['CONFIRMING'],
            'resubmit_eligible_primary_statuses' => ['FAILED'],
        ]);

        $this->assertTrue(collect($result['warnings'])->contains(
            fn ($w) => str_contains($w, 'will still work')
        ));
    }
}
