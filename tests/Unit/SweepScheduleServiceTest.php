<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Services\SweepScheduleService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SweepScheduleServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_interval_rule_is_due_when_never_run(): void
    {
        $service = new SweepScheduleService();
        $rule = new FireblocksSweepRule([
            'trigger_modes' => 'scheduled',
            'schedule_interval_minutes' => 30,
        ]);

        Carbon::setTestNow('2026-09-07 10:00:00');

        $this->assertTrue($service->isDueForScheduledRun($rule, ['scheduled_interval_minutes' => 5]));
    }

    public function test_interval_rule_is_not_due_before_interval_elapses(): void
    {
        $service = new SweepScheduleService();
        $rule = new FireblocksSweepRule([
            'trigger_modes' => 'scheduled',
            'schedule_interval_minutes' => 60,
            'schedule_last_run_at' => '2026-09-07 10:00:00',
        ]);

        Carbon::setTestNow('2026-09-07 10:30:00');

        $this->assertFalse($service->isDueForScheduledRun($rule));
    }

    public function test_interval_rule_is_due_after_interval_elapses(): void
    {
        $service = new SweepScheduleService();
        $rule = new FireblocksSweepRule([
            'trigger_modes' => 'scheduled',
            'schedule_interval_minutes' => 60,
            'schedule_last_run_at' => '2026-09-07 10:00:00',
        ]);

        Carbon::setTestNow('2026-09-07 11:00:00');

        $this->assertTrue($service->isDueForScheduledRun($rule));
    }

    public function test_daily_rule_waits_until_run_time(): void
    {
        $service = new SweepScheduleService();
        $rule = new FireblocksSweepRule([
            'trigger_modes' => 'scheduled',
            'schedule_run_at' => '03:00',
        ]);

        Carbon::setTestNow('2026-09-07 02:59:00');

        $this->assertFalse($service->isDueForScheduledRun($rule));
    }

    public function test_daily_rule_runs_once_per_day_after_time(): void
    {
        $service = new SweepScheduleService();
        $rule = new FireblocksSweepRule([
            'trigger_modes' => 'scheduled',
            'schedule_run_at' => '03:00',
        ]);

        Carbon::setTestNow('2026-09-07 03:05:00');
        $this->assertTrue($service->isDueForScheduledRun($rule));

        $rule->schedule_last_run_at = Carbon::parse('2026-09-07 03:06:00');
        $this->assertFalse($service->isDueForScheduledRun($rule));
    }

    public function test_describe_schedule_formats_daily_and_interval(): void
    {
        $service = new SweepScheduleService();

        $daily = new FireblocksSweepRule([
            'schedule_run_at' => '03:00',
        ]);
        $interval = new FireblocksSweepRule([
            'schedule_interval_minutes' => 45,
        ]);
        $default = new FireblocksSweepRule();

        $this->assertSame('Daily at 03:00', $service->describeSchedule($daily));
        $this->assertSame('Every 45 min', $service->describeSchedule($interval));
        $this->assertSame('Every 5 min (module default)', $service->describeSchedule($default, ['scheduled_interval_minutes' => 5]));

        $hourly = new FireblocksSweepRule(['schedule_interval_minutes' => 60]);
        $mixed = new FireblocksSweepRule(['schedule_interval_minutes' => 90]);
        $dailyInterval = new FireblocksSweepRule(['schedule_interval_minutes' => 1440]);

        $this->assertSame('Every 1 hour', $service->describeSchedule($hourly));
        $this->assertSame('Every 1 hr 30 min', $service->describeSchedule($mixed));
        $this->assertSame('Every 1 day', $service->describeSchedule($dailyInterval));
    }

    public function test_format_interval_human_readable(): void
    {
        $this->assertSame('Every 30 min', SweepScheduleService::formatIntervalHumanReadable(30));
        $this->assertSame('Every 2 hours', SweepScheduleService::formatIntervalHumanReadable(120));
        $this->assertSame('Every 2 days', SweepScheduleService::formatIntervalHumanReadable(2880));
    }

    public function test_should_advance_schedule_when_no_candidates(): void
    {
        $service = new SweepScheduleService();

        $this->assertTrue($service->shouldAdvanceScheduleAfterRun([], false));
    }

    public function test_should_not_advance_schedule_on_retry_worthy_skips(): void
    {
        $service = new SweepScheduleService();

        $this->assertFalse($service->shouldAdvanceScheduleAfterRun([
            ['status' => 'skipped', 'reason' => 'duplicate_external_tx_id'],
        ], true));

        $this->assertFalse($service->shouldAdvanceScheduleAfterRun([
            ['status' => 'skipped', 'reason' => 'in_flight'],
        ], true));

        $this->assertFalse($service->shouldAdvanceScheduleAfterRun([
            ['status' => 'failed'],
        ], true));
    }

    public function test_should_advance_schedule_on_submitted_or_handled_skip(): void
    {
        $service = new SweepScheduleService();

        $this->assertTrue($service->shouldAdvanceScheduleAfterRun([
            ['status' => 'submitted'],
        ], true));

        $this->assertTrue($service->shouldAdvanceScheduleAfterRun([
            ['status' => 'skipped', 'reason' => 'below_threshold'],
        ], true));

        $this->assertTrue($service->shouldAdvanceScheduleAfterRun([
            ['status' => 'skipped', 'reason' => 'in_flight'],
            ['status' => 'submitted'],
        ], true));
    }
}
