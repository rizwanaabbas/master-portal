<?php

namespace App\Console\Commands;

use App\Helpers\CardHelper;
use Illuminate\Console\Command;
use App\Helpers\VistaMoneyMerchantApis;
use App\Models\CardStatement;
use App\Models\UserCardLink;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GetCardsStatements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:card-statements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is a scheduled command for fetching all of the Cards Statements, using the Vista Money API, and storing it in the Database.';

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
        Log::info("Get Card Statements Console command started.");

        $linkedCards = UserCardLink::all();

        foreach ($linkedCards as $linkedCard) {
            CardHelper::checkAndDownloadStatements($linkedCard, false);
        }

        Log::info("The Cronjob ran successfully.");
    }
}
