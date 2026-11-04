<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;


class getCities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:cities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will get all cities in the world and store it in database.';

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

            # get states from the DB
            $states = State::where("country_id", $country->id)->get();

            foreach ($states as $state){

                # get cities for the state
                $response = Http::withHeaders([
                    'X-CSCAPI-KEY' => 'MFdMV0pxVEk2ZHNwdmdyTW1aYTdIa1VSVUNKMWxXeElMWUt3SWp5aQ==',
                    'accept' => 'application/json'
                ])->get('https://api.countrystatecity.in/v1/countries/'.$country->iso.'/states/'.$state->iso.'/cities');

            }

            # get states for the country


            print("\n========== total cities: ".count($response->object()));
            $processed = 1;
            # store states for the country
            foreach ($response->object() as $row){

                # check existence
                $isExists = City::where("name", $row->name)->where("state_id", $state->id)->first();

                if (!$isExists){

                    if ($row->name){
                        $city = new City();

                        $city->country_id = $country->id;
                        $city->state_id = $state->id;
                        $city->name = $row->name;

                        $city->save();
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
