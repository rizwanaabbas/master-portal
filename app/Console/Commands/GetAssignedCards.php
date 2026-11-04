<?php

namespace App\Console\Commands;

use App\Models\AssignedCard;
use App\Helpers\Common;
use App\Helpers\VistaMoneyMerchantApis;
use Exception;
use Illuminate\Console\Command;

class GetAssignedCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:assigned-cards';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the Assigned Card and populate the assigned_cards table.';

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
        $response = $vistaMoneyApis->getAssignedCards();

        self::saveAssignedCard($response);
        if ($response->last_page > 1) {
            for ($i = 1; $i <= $response->last_page; $i++) {
                $response = $vistaMoneyApis->getAssignedCards();

                self::saveAssignedCard($response);
            }
        }
        session(['next_page_url' => null]);
    }

    private function saveAssignedCard($response)
    {

        try {
            foreach ($response->data as $card) {
                $alreadyExists = AssignedCard::where('mpin', $card->mpin)->first();

                if ($alreadyExists) {
                    $newCard = $alreadyExists;
                } else {
                    $newCard = new AssignedCard();
                }

                $newCard->userName = $card->userName;
                $newCard->cardHolderName = $card->cardHolderName;
                $newCard->cardName = $card->cardName;
                $newCard->cardNumber = $card->cardNumber;
                $newCard->physical = $card->physical;
                $newCard->programType = $card->programType;
                $newCard->mpin = $card->mpin;
                $newCard->status = $card->status;
                $newCard->card_created_at = $card->created_at;
                $newCard->cType = $card->cType;

                $newCard->save();
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__, $response);

            echo $e->getMessage();
        }
    }
}
