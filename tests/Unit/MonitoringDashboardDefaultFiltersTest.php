<?php

namespace Tests\Unit;

use App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringConfigService;
use PHPUnit\Framework\TestCase;

class MonitoringDashboardDefaultFiltersTest extends TestCase
{
    public function test_direction_follows_monitored_directions(): void
    {
        $this->assertSame('outgoing', MonitoringConfigService::dashboardDefaultFilters([
            'monitored_directions' => 'outgoing',
            'default_filter_monitoring_state' => 'stuck',
        ])['direction']);

        $this->assertSame('incoming', MonitoringConfigService::dashboardDefaultFilters([
            'monitored_directions' => 'incoming',
        ])['direction']);

        $this->assertSame('all', MonitoringConfigService::dashboardDefaultFilters([
            'monitored_directions' => 'both',
        ])['direction']);
    }

    public function test_fb_filters_default_to_all(): void
    {
        $filters = MonitoringConfigService::dashboardDefaultFilters([
            'default_filter_monitoring_state' => 'action_pending',
        ]);

        $this->assertSame('all', $filters['fb_status']);
        $this->assertSame('all', $filters['fb_sub_status']);
        $this->assertSame('action_pending', $filters['monitoring_state']);
        $this->assertFalse($filters['stuck_only']);
        $this->assertSame('ledger', $filters['ledger_scope']);
    }
}
