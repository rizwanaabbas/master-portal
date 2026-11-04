<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class SweepScheduleService
{
    public function isDueForScheduledRun(FireblocksSweepRule $rule, ?array $config = null, ?CarbonInterface $now = null): bool
    {
        if (!$rule->supportsTrigger('scheduled')) {
            return false;
        }

        $config = $config ?? SweepConfigService::tryGet() ?? [];
        $now = $now ? Carbon::instance($now) : now();

        $runAt = $this->normalizedRunAt($rule->schedule_run_at);
        if ($runAt !== null) {
            return $this->isDueForDailyRun($rule, $runAt, $now);
        }

        return $this->isDueForIntervalRun($rule, $config, $now);
    }

    public function resolveIntervalMinutes(FireblocksSweepRule $rule, ?array $config = null): int
    {
        $config = $config ?? SweepConfigService::tryGet() ?? [];
        $interval = $rule->schedule_interval_minutes ?? ($config['scheduled_interval_minutes'] ?? 5);

        return max(1, (int) $interval);
    }

    public function describeSchedule(FireblocksSweepRule $rule, ?array $config = null): string
    {
        $runAt = $this->normalizedRunAt($rule->schedule_run_at);
        if ($runAt !== null) {
            return 'Daily at ' . $runAt;
        }

        $interval = $this->resolveIntervalMinutes($rule, $config);
        $usesModuleDefault = $rule->schedule_interval_minutes === null;
        $label = self::formatIntervalHumanReadable($interval);

        return $usesModuleDefault
            ? $label . ' (module default)'
            : $label;
    }

    public static function formatIntervalHumanReadable(int $minutes): string
    {
        $minutes = max(1, $minutes);

        if ($minutes >= 1440 && $minutes % 1440 === 0) {
            $days = intdiv($minutes, 1440);

            return 'Every ' . $days . ' ' . ($days === 1 ? 'day' : 'days');
        }

        if ($minutes >= 60) {
            $hours = intdiv($minutes, 60);
            $remainder = $minutes % 60;

            if ($remainder === 0) {
                return 'Every ' . $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
            }

            return sprintf(
                'Every %d %s %d min',
                $hours,
                $hours === 1 ? 'hr' : 'hrs',
                $remainder
            );
        }

        return 'Every ' . $minutes . ' min';
    }

    public function markScheduledRun(FireblocksSweepRule $rule, ?CarbonInterface $at = null): void
    {
        $rule->update([
            'schedule_last_run_at' => $at ?? now(),
        ]);
    }

    /**
     * Whether a scheduled cron tick should advance schedule_last_run_at.
     *
     * Retry-worthy outcomes (duplicate idempotency, in-flight, config errors) do not
     * consume the interval so the next cron can attempt again.
     *
     * @param  array<int, array<string, mixed>>  $results
     */
    public function shouldAdvanceScheduleAfterRun(array $results, bool $hadCandidates): bool
    {
        if (!$hadCandidates) {
            return true;
        }

        if ($results === []) {
            return false;
        }

        $retryReasons = [
            'duplicate_external_tx_id',
            'in_flight',
            'rule_disabled',
            'invalid_destination',
            'gas_estimate_failed',
            'gas_funding_vault_missing_asset',
            'gas_funding_vault_insufficient',
        ];

        foreach ($results as $result) {
            $status = (string) ($result['status'] ?? '');

            if (in_array($status, [
                FireblocksSweepJob::STATUS_SUBMITTED,
                FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
                FireblocksSweepJob::STATUS_COMPLETED,
            ], true)) {
                return true;
            }

            if ($status === FireblocksSweepJob::STATUS_SKIPPED) {
                $reason = (string) ($result['reason'] ?? $result['skip_reason'] ?? '');
                if ($reason === '' || !in_array($reason, $retryReasons, true)) {
                    return true;
                }

                continue;
            }

            if ($status === FireblocksSweepJob::STATUS_FAILED) {
                continue;
            }

            return true;
        }

        return false;
    }

    protected function isDueForDailyRun(FireblocksSweepRule $rule, string $runAt, CarbonInterface $now): bool
    {
        [$hour, $minute] = array_map('intval', explode(':', $runAt));
        $todaySlot = $now->copy()->setTime($hour, $minute, 0);

        if ($now->lt($todaySlot)) {
            return false;
        }

        $lastRun = $this->lastScheduledRunAt($rule);
        if ($lastRun !== null && $lastRun->isSameDay($now)) {
            return false;
        }

        return true;
    }

    protected function isDueForIntervalRun(FireblocksSweepRule $rule, array $config, CarbonInterface $now): bool
    {
        $interval = $this->resolveIntervalMinutes($rule, $config);
        $lastRun = $this->lastScheduledRunAt($rule);

        if ($lastRun === null) {
            return true;
        }

        return $lastRun->copy()->addMinutes($interval)->lte($now);
    }

    protected function lastScheduledRunAt(FireblocksSweepRule $rule): ?Carbon
    {
        if ($rule->schedule_last_run_at !== null) {
            return Carbon::parse($rule->schedule_last_run_at);
        }

        if ($rule->id === null) {
            return null;
        }

        $lastJobAt = FireblocksSweepJob::query()
            ->where('rule_id', $rule->id)
            ->where('trigger_source', 'scheduled')
            ->max('created_at');

        return $lastJobAt ? Carbon::parse($lastJobAt) : null;
    }

    protected function normalizedRunAt(?string $runAt): ?string
    {
        $runAt = trim((string) $runAt);
        if ($runAt === '') {
            return null;
        }

        if (!preg_match('/^(\d{2}):(\d{2})$/', $runAt, $matches)) {
            return null;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour > 23 || $minute > 59) {
            return null;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }
}
