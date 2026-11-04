<?php

namespace Tests\Unit;

use App\Modules\FireblocksTransactionsMonitoring\Services\ChainalysisComplianceSyncService;
use App\Modules\FireblocksTransactionsMonitoring\Services\TransactionMonitoringSync;
use Mockery;
use Tests\TestCase;

class ChainalysisComplianceSyncServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function service(): ChainalysisComplianceSyncService
    {
        $sync = Mockery::mock(TransactionMonitoringSync::class);

        return new ChainalysisComplianceSyncService($sync);
    }

    public function test_normalize_status_from_fireblocks_compliance_results(): void
    {
        $service = $this->service();

        $this->assertSame('COMPLETED', $service->normalizeStatus(['status' => 'Completed']));
        $this->assertSame('COMPLETED', $service->normalizeStatus(['status' => 'IncomingCompleted']));
        $this->assertSame('COMPLETED', $service->normalizeStatus(['status' => 'INCOMINGCOMPLETED']));
        $this->assertSame('COMPLETED', $service->normalizeStatus(['status' => 'INCOMING_COMPLETED']));
        $this->assertSame('FAILED', $service->normalizeStatus(['status' => 'Failed']));
        $this->assertSame('REJECTED', $service->normalizeStatus(['decision' => 'Rejected']));
    }

    public function test_incoming_completed_badge_says_completed(): void
    {
        $service = $this->service();
        $badge = $service->statusBadge('INCOMINGCOMPLETED');

        $this->assertSame('Completed', $badge['label']);
        $this->assertTrue($service->isSuccessStatus('INCOMINGCOMPLETED'));
    }

    public function test_resolve_for_grid_canonicalizes_incoming_completed(): void
    {
        $service = $this->service();
        $row = (object) [
            'chainalysis_status' => 'INCOMINGCOMPLETED',
            'chainalysis_scan_url' => '',
            'response_data' => [],
        ];

        $resolved = $service->resolveForGrid($row);

        $this->assertSame('COMPLETED', $resolved['status']);
    }

    public function test_build_scan_links_prefers_kyt_entity_portal(): void
    {
        $service = $this->service();
        $links = $service->buildScanLinks('0xabc', 'fb-tx-1', ['status' => 'Completed']);

        $this->assertStringContainsString('/entities/0xabc', (string) $links['address_entities_api']);
        $this->assertSame('https://kyt.chainalysis.com/entities/0xabc', $links['kyt_portal']);
        $this->assertSame($links['kyt_portal'], $links['primary']);
    }

    public function test_success_and_failure_status_helpers(): void
    {
        $service = $this->service();

        $this->assertTrue($service->isSuccessStatus('COMPLETED'));
        $this->assertTrue($service->isFailureStatus('REJECTED'));
        $this->assertFalse($service->isSuccessStatus('PENDING_SCREENING'));
    }

    public function test_normalize_address_screen_status_from_risk(): void
    {
        $service = $this->service();

        $this->assertSame('COMPLETE', $service->normalizeAddressScreenStatus(['risk' => 'low', 'status' => 'Complete']));
        $this->assertSame('PASSED', $service->normalizeAddressScreenStatus(['risk' => 'low']));
        $this->assertSame('FAILED', $service->normalizeAddressScreenStatus(['status' => 'FAILED', 'http_status' => 503]));
    }

    public function test_resolve_for_grid_falls_back_to_response_data_compliance(): void
    {
        $service = $this->service();
        $row = (object) [
            'chainalysis_status' => '',
            'chainalysis_scan_url' => '',
            'response_data' => [
                'complianceResults' => ['status' => 'Completed'],
                'chainalysis_scan_links' => ['primary' => 'https://example.test/entities/0x1'],
            ],
        ];

        $resolved = $service->resolveForGrid($row);

        $this->assertSame('COMPLETED', $resolved['status']);
        $this->assertSame('https://example.test/entities/0x1', $resolved['scan_url']);
    }

    public function test_resolve_for_grid_uses_kyt_entity_from_destination_like_detail(): void
    {
        $service = $this->service();
        $wallet = '0x5205D9401A7F3Dcb4E4C1c435adcac169A3077c2';
        $row = (object) [
            'chainalysis_status' => 'COMPLETED',
            'chainalysis_scan_url' => 'https://public.chainalysis.com/api/v1/entities/' . $wallet,
            'destination_address' => $wallet,
            'response_data' => [],
        ];

        $resolved = $service->resolveForGrid($row);

        $this->assertSame('https://kyt.chainalysis.com/entities/' . rawurlencode($wallet), $resolved['scan_url']);
    }

    public function test_resolve_for_grid_replaces_kyt_transaction_link_with_entity_when_wallet_known(): void
    {
        $service = $this->service();
        $row = (object) [
            'chainalysis_status' => 'COMPLETED',
            'chainalysis_scan_url' => 'https://kyt.chainalysis.com/transactions/ext-ref-1',
            'destination_address' => '0xabc',
            'response_data' => [],
        ];

        $resolved = $service->resolveForGrid($row);

        $this->assertSame('https://kyt.chainalysis.com/entities/0xabc', $resolved['scan_url']);
    }
}
