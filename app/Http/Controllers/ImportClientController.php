<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Imports\ImportClient;
use App\Models\Account;
use App\Models\Client;
use App\Models\ClientCompany;
use App\Models\ClientCompanyContact;
use App\Models\ClientIndividual;
use App\Models\ClientUser;
use App\Models\Currency;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

class ImportClientController extends Controller
{
    public function add()
    {
        return view("data-management.client-import");
    }

    public function importClients(Request $request)
    {
        $file = $request->file('file');
        $excelData = (new ImportClient)->toArray($file);

        $clients = $excelData[0];
        $admin = User::role("super admin")->first();

        // remove the header
        unset($clients[0]);

        foreach ($clients as $row){

            try{
                // special check for date converstion
                $row[1] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[1])->format("Y-m-d");

                if ($row[0] != ""){

                    // define client
                    if (!Client::where("name", trim($row[2]))->first()){

                        $client = self::createClient($row, $admin);

                        if ($client){

                            // define profile
                            $profile = self::CreateProfile($client, $row, $admin);

                            // define accounts
                            self::createAccounts($client, $profile, $row, $admin);

                            // define user
                            self::createUser($client, $row, $admin);
                        }
                    }
                }
            }catch (\Exception $e){
                dd ($row, $e);
            }

        }

        return redirect("/data-management/import/clients/new")->with("success", "Client Data Imported Successfully.");

    }

    private static function createUser($client, $row, $admin)
    {
        // create user if not existing
        $user = User::where("email", $row[5])->first();
        if (!$user){

            $user = new User();

            $user->name = $row[4];
            $user->email = $row[5];
            $user->password = bcrypt("cib@2022");
            $user->created_at = $row[1];

            $user->save();

            $user->assignRole("client");
        }else{
            $user->assignRole("client");
        }

        // add user to client
        Common::addUserToClient($client, $user);
    }

    private static function createAccounts($client, $profile, $row, $admin)
    {
        // check EUR
        if ($row[7] == "yes"){
            $currency = Currency::where("short", "EUR")->first();
            if ($currency){
                Account::create([
                    "client_id" => $client->id,
                    "company_id" => (strtolower($row[3]) == "company") ? $profile->id : 0,
                    "individual_id" => (strtolower($row[3]) == "individual") ? $profile->id : 0,
                    "type" => strtolower($row[3]),
                    "title" => $client->name." (EUR)",
                    "account_number" => Common::getNewClientAccountNumber($client->type, ($currency->is_crypto ? true : false)),
                    "account_currency" => $currency->id,
                    "is_active" => 1,
                    "created_by" => $admin->id,
                    "created_at" => $row[1]
                ]);
            }
        }

        // check USD
        if ($row[8] == "yes"){
            $currency = Currency::where("short", "USD")->first();
            if ($currency){
                Account::create([
                    "client_id" => $client->id,
                    "company_id" => (strtolower($row[3]) == "company") ? $profile->id : 0,
                    "individual_id" => (strtolower($row[3]) == "individual") ? $profile->id : 0,
                    "type" => strtolower($row[3]),
                    "title" => $client->name." (USD)",
                    "account_number" => Common::getNewClientAccountNumber($client->type, ($currency->is_crypto ? true : false)),
                    "account_currency" => $currency->id,
                    "is_active" => 1,
                    "created_by" => $admin->id,
                    "created_at" => $row[1]
                ]);
            }
        }

        // check CNY
        if ($row[9] == "yes"){
            $currency = Currency::where("short", "CNY")->first();
            if ($currency){
                Account::create([
                    "client_id" => $client->id,
                    "company_id" => (strtolower($row[3]) == "company") ? $profile->id : 0,
                    "individual_id" => (strtolower($row[3]) == "individual") ? $profile->id : 0,
                    "type" => strtolower($row[3]),
                    "title" => $client->name." (CNY)",
                    "account_number" => Common::getNewClientAccountNumber($client->type, ($currency->is_crypto ? true : false)),
                    "account_currency" => $currency->id,
                    "is_active" => 1,
                    "created_by" => $admin->id,
                    "created_at" => $row[1]
                ]);
            }
        }

        // check AED
        if ($row[10] == "yes"){
            $currency = Currency::where("short", "AED")->first();
            if ($currency){
                Account::create([
                    "client_id" => $client->id,
                    "company_id" => (strtolower($row[3]) == "company") ? $profile->id : 0,
                    "individual_id" => (strtolower($row[3]) == "individual") ? $profile->id : 0,
                    "type" => strtolower($row[3]),
                    "title" => $client->name." (AED)",
                    "account_number" => Common::getNewClientAccountNumber($client->type, ($currency->is_crypto ? true : false)),
                    "account_currency" => $currency->id,
                    "is_active" => 1,
                    "created_by" => $admin->id,
                    "created_at" => $row[1]
                ]);
            }
        }
    }

    private static function CreateProfile($client, $row, $admin)
    {
        $type = strtolower($row[3]);

        if ($type == "company"){

            // create company profile
            $company = ClientCompany::where("client_id", $client->id)->first();

            if (!$company){
                $company = new ClientCompany();
            }

            $company->client_id = $client->id;
            $company->created_by = $admin->id;
            $company->created_at = $row[1];

            $company->save();

            // create company contact
            $contact = ClientCompanyContact::where("client_id", $client->id)->first();

            if (!$contact){
                $contact = new ClientCompanyContact();
            }

            $contact->client_id = $client->id;
            $contact->company_id = $company->id;
            $contact->name = $client->name;
            $contact->email_address = $row[5];
            $contact->mobile_number = $row[6];
            $contact->designation = "Owner";
            $contact->created_by = $admin->id;
            $contact->created_at = $row[1];

            $contact->save();

            return $company;

        }

        if ($type == "individual"){

            // create individual
            $individual = ClientIndividual::where("client_id", $client->id)->first();

            if (!$individual){
                $individual = new ClientIndividual();
            }

            $individual->client_id = $client->id;
            $individual->email_address = $row[5];
            $individual->mobile_number = $row[6];
            $individual->created_by = $admin->id;
            $individual->created_at = $row[1];

            $individual->save();

            return $individual;
        }
    }

    private static function createClient($row, $admin)
    {
        $client = new Client();

//        $client->code = $row[0];
        $client->name = $row[2];
        $client->type = strtolower($row[3]);
        $client->status = "Approved";
        $client->send_welcome_email = 1;
        $client->send_ssi = 1;
        $client->create_user = 1;
        $client->created_at = $row[1];
        $client->created_by = $admin->id;

        $client->save();

        return $client;
    }
}
