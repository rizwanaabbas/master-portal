<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Services\TaggedVaultScanService;
use PHPUnit\Framework\TestCase;

class TaggedVaultScanServiceTest extends TestCase
{
    public function test_extract_available_balance_from_paged_account_payload(): void
    {
        $service = new TaggedVaultScanService();

        $available = $service->extractAvailableBalance([
            'id' => '42',
            'assets' => [
                ['id' => 'ETH_TEST', 'available' => '1.5', 'total' => '2.0'],
                ['id' => 'TRX_TEST', 'available' => '0', 'total' => '10'],
            ],
        ], 'ETH_TEST');

        $this->assertSame('1.5', $available);
    }

    public function test_extract_available_returns_zero_when_asset_missing(): void
    {
        $service = new TaggedVaultScanService();

        $this->assertSame('0', $service->extractAvailableBalance(['assets' => []], 'ETH_TEST'));
    }

    public function test_resolve_tag_id_from_config(): void
    {
        $service = new TaggedVaultScanService();

        $this->assertSame(
            '33c56eac-601a-47f2-b265-bee94c4c4246',
            $service->resolveTagId(['client_intermediary_tag_id' => '33c56eac-601a-47f2-b265-bee94c4c4246'])
        );
    }

    public function test_account_has_tag_matches_webhook_payload_tags(): void
    {
        $service = new TaggedVaultScanService();
        $tagId = 'bc526b1a-670f-44f3-b71b-110573994f5f';

        $this->assertTrue($service->accountHasTag([
            ['id' => $tagId, 'label' => 'CI-test'],
        ], $tagId));

        $this->assertFalse($service->accountHasTag([
            ['id' => 'other-tag', 'label' => 'Other'],
        ], $tagId));
    }

    public function test_account_has_tag_ignores_hardcoded_client_intermediary_name(): void
    {
        $service = new TaggedVaultScanService();
        $tagId = 'bc526b1a-670f-44f3-b71b-110573994f5f';

        $this->assertFalse($service->accountHasTag(['client-intermediary'], $tagId));
        $this->assertFalse($service->accountHasTag([
            ['label' => 'client-intermediary', 'name' => 'client-intermediary'],
        ], $tagId));
        $this->assertTrue($service->accountHasTag([
            ['tagId' => $tagId, 'label' => 'client-intermediary'],
        ], $tagId));
    }

    public function test_resolve_tag_id_from_nested_module_config_value(): void
    {
        $service = new TaggedVaultScanService();

        $this->assertSame(
            'bc526b1a-670f-44f3-b71b-110573994f5f',
            $service->resolveTagId([
                'client_intermediary_tag_id' => ['value' => 'bc526b1a-670f-44f3-b71b-110573994f5f'],
            ])
        );
    }

    public function test_vault_has_client_intermediary_tag_uses_webhook_uuid_without_api(): void
    {
        $service = new TaggedVaultScanService();
        $config = ['client_intermediary_tag_id' => 'bc526b1a-670f-44f3-b71b-110573994f5f'];

        $this->assertTrue($service->vaultHasClientIntermediaryTag(
            '115',
            $config,
            [['id' => 'bc526b1a-670f-44f3-b71b-110573994f5f', 'label' => 'CI-test']]
        ));
    }
}
