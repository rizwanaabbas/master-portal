<?php

namespace Tests\Unit;

use Fireblocks\Sdk\Api\Vaults;
use PHPUnit\Framework\TestCase;

class VaultsPagedQueryTest extends TestCase
{
    public function test_build_paged_query_string_includes_tag_ids_fallback(): void
    {
        $query = Vaults::buildPagedQueryString([
            'includeTagIds' => '33c56eac-601a-47f2-b265-bee94c4c4246',
            'tagIds' => '33c56eac-601a-47f2-b265-bee94c4c4246',
            'limit' => 500,
            'assetId' => 'USDC_ETH_TEST5_0GER',
        ]);

        $this->assertStringContainsString('includeTagIds=33c56eac-601a-47f2-b265-bee94c4c4246', $query);
        $this->assertStringContainsString('tagIds=33c56eac-601a-47f2-b265-bee94c4c4246', $query);
        $this->assertStringNotContainsString('includeTagIds%5B0%5D', $query);
        $this->assertStringContainsString('limit=500', $query);
        $this->assertStringContainsString('assetId=USDC_ETH_TEST5_0GER', $query);
    }

    public function test_build_paged_query_string_supports_multiple_tag_ids(): void
    {
        $query = Vaults::buildPagedQueryString([
            'includeTagIds' => [
                '33c56eac-601a-47f2-b265-bee94c4c4246',
                'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            ],
        ]);

        $this->assertSame(
            'includeTagIds=33c56eac-601a-47f2-b265-bee94c4c4246&includeTagIds=aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            $query
        );
    }
}
