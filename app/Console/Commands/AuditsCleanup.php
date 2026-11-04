<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OwenIt\Auditing\Models\Audit;
use Carbon\Carbon;

class AuditsCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audits:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Audit Logs older than one (1) year, on daily basis';

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
        return Audit::where('created_at', '<=', Carbon::now()->subYear()->toDateTimeString())->delete();
    }
}
