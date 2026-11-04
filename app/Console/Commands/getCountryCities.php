<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Country;
use App\Models\CountryCity;
use App\Models\State;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;


class getCountryCities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:ccities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will get all cities in the world for a country and store it in database.';

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

            # get cities for the country
            $response = Http::withHeaders([
                'X-CSCAPI-KEY' => 'MFdMV0pxVEk2ZHNwdmdyTW1aYTdIa1VSVUNKMWxXeElMWUt3SWp5aQ==',
                'accept' => 'application/json'
            ])->get('https://api.countrystatecity.in/v1/countries/'.$country->iso.'/cities');

            print("\n========== total cities: ".count($response->object()));
            $processed = 1;

            # store cities for the country
            foreach ($response->object() as $row){

                # check existence
                $isExists = CountryCity::where("name", $row->name)->where("country_id", $country->id)->first();

                if (!$isExists){

                    if ($row->name){
                        $city = new CountryCity();

                        $city->country_id = $country->id;
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
