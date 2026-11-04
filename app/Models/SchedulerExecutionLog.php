<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchedulerExecutionLog extends Model
{
    protected $fillable = [
        'command',
        'description',
        'schedule_expression',
        'status',
        'output',
        'error_message',
        'duration_ms',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
