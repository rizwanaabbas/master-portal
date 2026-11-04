<?php

namespace Tests\Unit;

use App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringChartService;
use ReflectionMethod;
use Tests\TestCase;

class MonitoringChartServiceTest extends TestCase
{
    public function test_series_builders_return_labels_values_and_colors(): void
    {
        $service = new MonitoringChartService();

        $stuckAge = (new ReflectionMethod($service, 'buildStuckAgeSeries'))->invoke($service, [
            '0-15m' => 1,
            '4h+' => 2,
        ]);

        $this->assertSame(['0-15m', '15-60m', '1-4h', '4h+'], $stuckAge['labels']);
        $this->assertSame([1, 0, 0, 2], $stuckAge['values']);
        $this->assertCount(4, $stuckAge['colors']);

        $actions = (new ReflectionMethod($service, 'buildActionSeries'))->invoke($service, [
            'boost' => 3,
            'sync' => 1,
        ]);

        $this->assertSame(['Boost', 'Drop', 'Resubmit', 'Sync'], $actions['labels']);
        $this->assertSame([3, 0, 0, 1], $actions['values']);
        $this->assertSame('#28a745', $actions['colors'][0]);
    }
}
