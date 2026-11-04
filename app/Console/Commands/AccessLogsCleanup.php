<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AccessLogs;
use Carbon\Carbon;

class AccessLogsCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'access-logs:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Access Logs older than one (1) year, on daily basis';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        AccessLogs::where('created_at', '<=', Carbon::now()->subMonth()->toDateTimeString())->delete();
    }
}
