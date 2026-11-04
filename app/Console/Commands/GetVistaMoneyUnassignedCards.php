<?php

namespace App\Console\Commands;

use App\Models\UnassignedKitNumber;
use App\Helpers\VistaMoneyMerchantApis;
use Illuminate\Console\Command;

class GetVistaMoneyUnassignedCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:kit-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Unassigned Card Kit-numbers and store it in the database';

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
        $vistaMoneyApis = new VistaMoneyMerchantApis();
        $kitNumbers = $vistaMoneyApis->getUnassignedKitNumbers();

        foreach ($kitNumbers as $kitNumber) {
            $alreadyExists = UnassignedKitNumber::where('kit_number', $kitNumber->itemName)->first();

            if (!$alreadyExists) {
                $unassignedKitNumber = new UnassignedKitNumber();

                echo ".";
                $unassignedKitNumber->kit_number_id = $kitNumber->id;
                $unassignedKitNumber->kit_number = $kitNumber->itemName;

                $unassignedKitNumber->save();
            }
        }
    }
}
