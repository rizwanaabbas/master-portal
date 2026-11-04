<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \Illuminate\Console\Events\ScheduledTaskStarting::class => [
            [\App\Listeners\LogScheduledTaskExecution::class, 'handleStarting'],
        ],
        \Illuminate\Console\Events\ScheduledTaskFinished::class => [
            [\App\Listeners\LogScheduledTaskExecution::class, 'handleFinished'],
        ],
        \Illuminate\Console\Events\ScheduledTaskFailed::class => [
            [\App\Listeners\LogScheduledTaskExecution::class, 'handleFailed'],
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
