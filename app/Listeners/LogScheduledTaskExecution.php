<?php

namespace App\Listeners;

use App\Models\SchedulerExecutionLog;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;

class LogScheduledTaskExecution
{
    protected static array $running = [];

    public function handleStarting(ScheduledTaskStarting $event): void
    {
        $command = $this->extractCommand($event->task);

        $log = SchedulerExecutionLog::create([
            'command' => $command,
            'description' => $event->task->description ?? null,
            'schedule_expression' => $event->task->expression ?? null,
            'status' => 'running',
            'started_at' => now(),
        ]);

        self::$running[$command] = $log->id;
    }

    public function handleFinished(ScheduledTaskFinished $event): void
    {
        $command = $this->extractCommand($event->task);
        $logId = self::$running[$command] ?? null;

        if (!$logId) {
            return;
        }

        $started = SchedulerExecutionLog::find($logId);
        if (!$started) {
            return;
        }

        $started->update([
            'status' => 'success',
            'finished_at' => now(),
            'duration_ms' => $started->started_at ? (int) $started->started_at->diffInMilliseconds(now()) : null,
        ]);

        unset(self::$running[$command]);
    }

    public function handleFailed(ScheduledTaskFailed $event): void
    {
        $command = $this->extractCommand($event->task);
        $logId = self::$running[$command] ?? null;

        if (!$logId) {
            SchedulerExecutionLog::create([
                'command' => $command,
                'description' => $event->task->description ?? null,
                'schedule_expression' => $event->task->expression ?? null,
                'status' => 'failed',
                'error_message' => $event->exception->getMessage(),
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            return;
        }

        $started = SchedulerExecutionLog::find($logId);
        if (!$started) {
            return;
        }

        $started->update([
            'status' => 'failed',
            'error_message' => $event->exception->getMessage(),
            'finished_at' => now(),
            'duration_ms' => $started->started_at ? (int) $started->started_at->diffInMilliseconds(now()) : null,
        ]);

        unset(self::$running[$command]);
    }

    protected function extractCommand($task): string
    {
        if (property_exists($task, 'command') && !empty($task->command)) {
            return trim(str_replace([PHP_BINARY, 'artisan'], '', (string) $task->command));
        }

        if (property_exists($task, 'description') && !empty($task->description)) {
            return (string) $task->description;
        }

        return 'unknown';
    }
}
