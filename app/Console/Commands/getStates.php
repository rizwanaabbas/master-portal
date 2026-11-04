<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\State;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;


class getStates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:states';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will get all states in the world and store it in database.';

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
        $countries = Country::all();

        foreach ($countries as $country){

            # get states for the country
            $response = Http::withHeaders([
                'X-CSCAPI-KEY' => 'MFdMV0pxVEk2ZHNwdmdyTW1aYTdIa1VSVUNKMWxXeElMWUt3SWp5aQ==',
                'accept' => 'application/json'
            ])->get('https://api.countrystatecity.in/v1/countries/'.$country->iso.'/states');

            print("\n========== total states: ".count($response->object()));
            $processed = 1;
            # store states for the country
            foreach ($response->object() as $row){

                # check existence
                $isExists = State::where("name", $row->name)->first();

                if (!$isExists){

                    if ($row->name){
                        $state = new State();

                        $state->country_id = $country->id;
                        $state->name = $row->name;
                        $state->iso = $row->iso2;

                        $state->save();
                        print("\n-----------processed: ".$processed++);
                    }else{
                        print("\n xxxxxx name does not exists: ".$processed++);
                    }
                }else{
                    print("\n xxxxxx existing: ".$processed++);
                }
            }
        }
        print("\nxxxxxx finished xxxxxxx");
    }
}
