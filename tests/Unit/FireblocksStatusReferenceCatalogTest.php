<?php

namespace Tests\Unit;

use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Modules\Fireblocks\Support\FireblocksStatusCatalog;
use Tests\TestCase;

class FireblocksStatusReferenceCatalogTest extends TestCase
{
    public function test_catalog_includes_all_primary_statuses(): void
    {
        $catalog = FireblocksStatusHelper::statusReferenceCatalog();
        $this->assertCount(count(FireblocksStatusHelper::allPrimaryStatuses()), $catalog['primary_statuses']);
        $this->assertNotNull(collect($catalog['primary_statuses'])->firstWhere('code', 'SIGNED'));
    }

    public function test_catalog_includes_all_documented_substatuses(): void
    {
        $catalog = FireblocksStatusHelper::statusReferenceCatalog();
        $canonicalCount = count(array_filter(
            FireblocksStatusCatalog::substatuses(),
            fn ($meta) => !isset($meta['canonical'])
        ));
        $this->assertCount($canonicalCount, $catalog['substatuses']);
        $this->assertNotNull(collect($catalog['substatuses'])->firstWhere('code', 'MISSING_TAG_OR_MEMO'));
    }

    public function test_filter_options_cover_full_substatus_catalog(): void
    {
        $filterCodes = [];
        foreach (FireblocksStatusHelper::substatusFilterOptions() as $items) {
            $filterCodes = array_merge($filterCodes, array_keys($items));
        }

        foreach (array_keys(FireblocksStatusHelper::substatusMessages()) as $code) {
            $this->assertContains($code, $filterCodes, "Missing {$code} in substatus filter dropdown");
        }
    }

    public function test_primary_filter_options_use_console_labels(): void
    {
        $options = FireblocksStatusHelper::primaryStatusFilterOptions();
        $this->assertSame('Pending Screening', $options['PENDING_AML_SCREENING']);
        $this->assertSame('Signed', $options['SIGNED']);
    }

    public function test_internal_status_filter_options_match_catalog(): void
    {
        $options = FireblocksStatusHelper::internalStatusFilterOptions();
        $catalog = FireblocksStatusHelper::statusReferenceCatalog();
        $this->assertCount(count($catalog['internal_statuses']), $options);
        foreach ($catalog['internal_statuses'] as $row) {
            $this->assertArrayHasKey($row['label'], $options);
        }
    }

    public function test_internal_status_filter_uses_primary_and_sub_status_not_column_only(): void
    {
        $query = \App\Models\FireblocksTransaction::query();
        FireblocksStatusHelper::applyInternalStatusFilter($query, 'Approved');
        $sql = $query->toSql();

        $this->assertStringContainsString('internal_status', $sql);
        $this->assertContains('COMPLETED', $query->getBindings());
        $this->assertContains('CONFIRMED', $query->getBindings());
    }

    public function test_resolve_matches_catalog_internal_for_failed(): void
    {
        $resolved = FireblocksStatusHelper::resolve('FAILED', 'FAIL_ON_LOW_FEE');
        $this->assertSame('Rejected', $resolved['internal_status']);
    }

    public function test_substatus_alias_normalization_for_filters(): void
    {
        $codes = FireblocksStatusHelper::substatusFilterMatchCodes('UNAUTHORISED__DEVICE');
        $this->assertContains('UNAUTHORISED_DEVICE', $codes);
        $this->assertContains('UNAUTHORISED__DEVICE', $codes);
    }

    public function test_catalog_reflects_configured_action_eligibility(): void
    {
        $catalog = FireblocksStatusHelper::statusReferenceCatalog([
            'boost_eligible_primary_statuses' => ['CONFIRMING'],
            'drop_eligible_primary_statuses' => ['CONFIRMING'],
            'resubmit_eligible_primary_statuses' => ['FAILED'],
            'retriable_substatuses' => ['FAIL_ON_LOW_FEE'],
            'non_retriable_substatuses' => [],
        ]);

        $confirming = collect($catalog['primary_statuses'])->firstWhere('code', 'CONFIRMING');
        $failed = collect($catalog['primary_statuses'])->firstWhere('code', 'FAILED');
        $completed = collect($catalog['primary_statuses'])->firstWhere('code', 'COMPLETED');

        $this->assertSame('Yes', $confirming['boost']);
        $this->assertSame('Yes (EVM only)', $confirming['drop']);
        $this->assertSame('Yes', $failed['resubmit']);
        $this->assertSame('No', $completed['boost']);
    }
}
