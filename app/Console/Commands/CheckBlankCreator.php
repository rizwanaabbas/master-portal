<?php

namespace App\Console\Commands;

use App\Models\ClientUser;
use Illuminate\Console\Command;

class CheckBlankCreator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:blank-creator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will check if there are any clients without user attachment.';

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
        $cus = ClientUser::all();

        foreach($cus as $cu){

            if ($cu->client && !$cu->user){
                echo "\nClient: ".$cu->client->name." is not connected to user id: ".$cu->user_id;
            }
        }
    }
}
