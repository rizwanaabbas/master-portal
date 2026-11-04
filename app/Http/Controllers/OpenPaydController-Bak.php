<?php

namespace App\Http\Controllers;

use App\Models\BeneficiaryRequiredFields;
use Illuminate\Http\Request;
use App\Helpers\ClientHelper;
use App\Helpers\Common;
use App\Helpers\OpenpaydApis;
use App\Models\ClientBeneficiary;
use App\Models\Account;
use App\Models\Client;
use App\Models\Country;
use App\Models\Currency;
use App\Models\OpenPaydBankAccount;
use App\Models\OpenPaydLinkedClients;
use App\Models\OpenpaydBankBeneficiary;
use App\Models\TransactionMethod;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OpenPaydController extends Controller
{
    // public function internalTransfer() {

    //     $client_ids = Common::getClientIdsByUserId(Auth::user()->id);
    //     $currencies = Currency::where("is_active", true)->where("is_crypto", false)->select("id")->get();
    //     $accounts = Account::where('is_active', true)->whereIn("client_id", $client_ids)->whereIn("account_currency", $currencies)->select("id", "title", "account_number")->get();


    //     return view("client-portal.transaction-request.openpayd.internal-transfer", compact('accounts'));
    // }
    /*
        Created On: 29 July 2025
        Created By: Rizwan Abbas
        POE: Client Account management Create IBAN button
    */

    public function createIBAN(Request $request)
    {

        $request->validate([
            'account_id' => 'required'
        ]);
        if (!Common::isModuleEnabled('OpenPayd')) {
            return redirect(url()->previous())->with('error', 'OpenPayd Module is disabled')->with('formnumber', 3);
        }

        try {
            if (!empty($request->get("account_id"))) {

                //foreach ($request->get("openpayd_currency") as $currency) {
                $account = Account::find($request->get("account_id"));
                $client = ClientHelper::getClientByAccountId($account->id);
                $currency = $account->currency;
                if (!empty($currency))
                    $currency_code = $currency->short;
                else
                    return redirect(url()->previous())->with('error', 'Account currency not found')->with('formnumber', 3);


                $exists = OpenPaydBankAccount::where('client_id', $client->id)->where('currency', $currency->id)->where('vendor_name','openpayd')->first();
                if ($exists) {
                    return redirect()->back()->with('error', "$currency_code currency account already exits.");
                }

                //$iban_currencies_ids = $request->get('openpayd_currency');

                // if (empty($client->individual->residence_country_id) || empty($client->individual->city)) {
                //     return redirect(url()->previous())->with('error', 'Please fill in both the City and Country fields for the individual.')->with('formnumber', 3);
                // }
                // if (empty($client->individual->employer->employer_address)) {
                //     return redirect(url()->previous())->with('error', 'Please fill in the individual employer address.')->with('formnumber', 3);
                // }



                // dd($client->individual->residence_country_id);
                //if (in_array($account->currency->id) {

                $openpaydApis = new OpenpaydApis();
                $openpayd_client = $openpaydApis->storeOpenPaydClient($client);
                //dd($openpayd_client);

                if ($openpayd_client && empty($openpayd_client->errorCode)) {
                    $bank_account = $openpaydApis->storeOpenPaydAccount($account, $openpayd_client);
                    // dd($bank_account);
                    if (!empty($bank_account->errorCode)) {

                        return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
                    }

                    //$account->account_number = $bank_account->account_number;
                    $account->openpayd_account_id = $bank_account->id;
                    $account->save();
                    return redirect(url()->previous())->with('success', "IBAN has been created successfully.");
                } else if (!empty($openpayd_client->errorCode)) {

                    return redirect(url()->previous())->with('error', "OpenPayd Error: " . $openpayd_client->errorCode);
                }
                //}
                //}
            }
        } catch (Exception $e) {
            Log::error('Error creating IBAN: ' . $e->getMessage());
            return redirect(url()->previous())->with('error', 'Error creating IBAN: ' . $e->getMessage());
        }
    }

    public function synchIBAN($account_id)
    {
        $data = array('errorCode' => 'failed');
        if (!empty($account_id)) {
            $openpaydApis = new OpenpaydApis();
            $data = $openpaydApis->getOpenPaydBankAccount($account_id);
            if (!empty($data) && empty($data->errorCode)) {
                $bankAccount = OpenPaydBankAccount::where('openpayd_account_id', $account_id)->first();
                $update = [

                    'status' => $data->status,
                    'iban' => $data->iban ?? 'PENDING',
                    'bic' => $data->bic ?? 'xxxxxx',
                    'account_number' => $data->bankAccountNumber ?? 'xxxxxx',
                ];
                $bankAccount->update($update);
                //$open_payd_bank_account->save();
                $data = array('errorCode' => 'success', 'data' => $update);
            }
        }

        return response()->json($data);
    }
    public function getMainBankAccounts(Request $request)
    {
        $request->validate([
            'transaction_method_id' => 'required',
            'currency' => 'required'
        ]);
        $currency_code = $request->get('currency');
        $transaction_method_id = $request->get('transaction_method_id');
        //check if transaction method belongs to OpenPayd
        $transaction_method = TransactionMethod::where('id', $transaction_method_id)->first();
        // dd($transaction_method);
        $data = array('empty' => true);
        if (empty($transaction_method)) {
            $data = array('empty' => true);
        }
        //dd($transaction_method->module);
        if (!empty($transaction_method->module) && $transaction_method->module->name == 'OpenPayd') {
            $openpaydApis = new OpenpaydApis();
            $bank_accounts = $openpaydApis->getOpenPaydBankAccounts($currency_code);
            if ($bank_accounts && empty($bank_accounts->errorCode)) {

                return response()->json($bank_accounts);
            }
        } else {
            $data = array('empty' => true);
        }
        return response()->json($data);
    }

    // public function generateIBAN(Request $request)
    // {
    //     $request->validate([
    //         'account_id' => 'required',
    //         'client_id' => 'required',
    //     ]);

    //     if (!Common::isModuleEnabled('OpenPayd')) {
    //         return redirect()->back()->with('error', 'OpenPayd is not enabled.')->with('formnumber', 3);
    //     }

    //     $account = Account::find($request->get("account_id"));
    //     $openpaydApis = new OpenpaydApis();
    //     $allowedCurrencies = $openpaydApis->getAllowedCurrencies(true);
    //     $client = ClientHelper::getClientByAccountId($account->id);

    //     if ($client->type == 'individual' && (!$client->individual->city || !$client->individual->residence_country_id)) {
    //         return redirect()->back()->with('error', 'Please fill in both the City and Country fields for the Individual.')->with('formnumber', 3);
    //     } elseif ($client->type == 'company' && (!$client->company->company_city || !$client->company->company_country)) {
    //         return redirect()->back()->with('error', 'Please fill in both the City and Country fields for the Company.')->with('formnumber', 3);
    //     }

    //     if (!in_array(strtoupper($account->currency->short), $allowedCurrencies)) {
    //         return redirect()->back()->with('error', 'This currency ' . strtoupper($account->currency->short) . ' is not supported by OpenPayd.')->with('formnumber', 3);
    //     }

    //     $openpayd_linked_client = $openpaydApis->storeOpenPaydClient($client);
    //     if (empty($openpayd_linked_client) || empty($openpayd_linked_client->linked_client_id)) {
    //         $error = !empty($openpayd_linked_client->errors) ? $openpaydApis->getErrorByErrorCode($openpayd_linked_client->errors[0]->errorCode) : $openpaydApis->getErrorByErrorCode($openpayd_linked_client->errorCode);
    //         return redirect()->back()->with('error', $error)->with('formnumber', 3);
    //     }

    //     $bank_account = $openpaydApis->storeOpenPaydAccount($account, $openpayd_linked_client);
    //     if (!empty($bank_account->errorCode)) {
    //         $error = !empty($bank_account->errors) ? $openpaydApis->getErrorByErrorCode($bank_account->errors[0]->errorCode) : $openpaydApis->getErrorByErrorCode($bank_account->errorCode);
    //         return redirect()->back()->with('error', $error)->with('formnumber', 3);
    //     }

    //     if (!empty($bank_account)) {
    //         return redirect()->back()->with('success', 'IBAN has been generated successfully!')->with('formnumber', 3);
    //     }

    //     return redirect()->back()->with('error', 'Something went wrong, please try again later.')->with('formnumber', 3);
    // }

    public function ibanInfo($account_id)
    {

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        if (!empty($account_id)) {
            $bank_account = OpenPaydBankAccount::where('account_id', $account_id)
                ->select('iban', 'bic', 'currency', 'account_number', 'bank_account_holder_name', 'bank_name', 'bank_address')
                ->first();
            return response()->json([
                'status' => 'success',
                'data' => $bank_account,
            ], 200);
        }
    }

    public function baneficiaryInfo($beneficiary_id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        if (!empty($beneficiary_id)) {
            $beneficiary = ClientBeneficiary::where('id', $beneficiary_id)->first();
            $bank_beneficiary = OpenpaydBankBeneficiary::where('parent_beneficiary_id', $beneficiary['id'])
                ->select('id', 'iban', 'bic', 'currency', 'account_number', 'bank_account_holder_name', 'bank_name', 'bank_address')
                ->first();
            return response()->json([
                'status' => 'success',
                'data' => $bank_beneficiary,
            ], 200);
        }
    }

    public function openpaydSyncData(Request $request)
    {
        $openpayd_api = new OpenpaydApis();
        $openpayd_clients = $openpayd_api->getLinkedClients()->content;
        // dd($openpayd_clients);
        // $openpayd_clients_accounts = $openpayd_api->getAllClientAccountList();
        // dd($openpayd_clients_accounts);
        $local_clients = [];
        foreach ($openpayd_clients as $client) {
            $local_client = OpenPaydlinkedClients::where('account_holder_id', $client->accountHolderId)->first();
            if ($local_client) {
                $local_clients[] = $local_client;
            }
        }
        // dd($local_clients);
        return view("settings.openpayd.sync-data", compact('openpayd_clients', 'local_clients'));
    }

    public function getOpenPaydClient($id)
    {
        $openpayd_api = new OpenpaydApis();
        $openpayd_clients = $openpayd_api->getLinkedClients($id);
        $openpayd_clients_accounts = $openpayd_api->getAllClientAccountList($id)->content;
        $openpayd_clients_beneficiaries = $openpayd_api->getClientBeneficiaries($id)->content;

        $local_client = OpenPaydLinkedClients::where('account_holder_id', $openpayd_clients->accountHolderId)->first();

        if (!empty($local_client)) {
            $internal_account_ids = OpenPaydBankAccount::where('client_id', $local_client->client_id)
                ->pluck('internal_account_id')
                ->toArray();
            $openpayd_clients_accounts = array_filter($openpayd_clients_accounts, function ($account) use ($internal_account_ids) {
                return !in_array($account->internalAccountId, $internal_account_ids);
            });
        }

        $openpayd_clients_ids = OpenPaydLinkedClients::select('client_id')->pluck('client_id')->toArray();
        $clients = Client::where("status", "Approved")->whereNotIn('id', $openpayd_clients_ids)->get();
        $local_client_beneficiaries = OpenpaydBankBeneficiary::where('account_holder_id', $id)->get();

        $local_beneficiaries_ids = $local_client_beneficiaries->pluck('beneficiary_id')->toArray();

        $openpayd_clients_beneficiaries = array_filter($openpayd_clients_beneficiaries, function ($beneficiary) use ($local_beneficiaries_ids) {
            return !in_array($beneficiary->id, $local_beneficiaries_ids);
        });

        return view("settings.openpayd.view-client  ", compact('openpayd_clients', 'local_client', 'openpayd_clients_accounts', 'clients', 'openpayd_clients_beneficiaries', 'local_client_beneficiaries'));
    }

    public function syncOpenpaydClient(Request $request)
    {
        $account_holder_id = $request->get('account_holder_id');
        $client_id = $request->get('client_id');
        $openpayd_client = OpenPaydLinkedClients::where('account_holder_id', $account_holder_id)->first();
        $client = Client::find($client_id);
        $openpayd_api = new OpenpaydApis();

        try {
            if (empty($openpayd_client)) {
                // Getting openpayd client.
                $get_openpayd_linked_client = $openpayd_api->getLinkedClients($account_holder_id);
                // store openpayd linked client in CAIB DB.
                if (!empty($get_openpayd_linked_client)) {
                    Log::info('Openpayd Linked Client Account: ' . json_encode($get_openpayd_linked_client));

                    if (empty($client)) return redirect()->back()->with('error', 'Invalid client id.');

                    $openpayd_client = new OpenPaydlinkedClients();
                    $openpayd_client->client_id = $client->id;
                    $openpayd_client->account_holder_id = $get_openpayd_linked_client->accountHolderId;
                    $openpayd_client->status = $get_openpayd_linked_client->status;
                    $openpayd_client->kyc_status = $get_openpayd_linked_client->kycStatus;
                    $openpayd_client->linked_client_id = $get_openpayd_linked_client->id;
                    $openpayd_client->response_data = json_encode($get_openpayd_linked_client);

                    if ($get_openpayd_linked_client->clientType == 'INDIVIDUAL') {
                        $openpayd_client->country = $get_openpayd_linked_client->individual->address->country;
                    } else if ($get_openpayd_linked_client->clientType == 'BUSINESS') {
                        $openpayd_client->country = $get_openpayd_linked_client->company->registeredAddress->country;
                    }

                    $openpayd_client->save();
                    // dd($new_openpayd_client);
                }
            }

            // Get client openpayd accounts.
            $openpayd_clients_accounts = $openpayd_api->getAllClientAccountList($account_holder_id)->content;

            foreach ($openpayd_clients_accounts as $op_account) {
                $loacl_accounts = OpenPaydBankAccount::where('internal_account_id', $op_account->internalAccountId)->first();

                DB::beginTransaction();
                if (empty($loacl_accounts)) {


                    $get_account = $openpayd_api->getClientBankAccountList($op_account->internalAccountId, $op_account->accountHolderId);
                    $bank_account = $get_account[0];
                    // dd($bank_account);

                    $account_title = $client->name . " (" . $bank_account->currency . " - SEPA) - SEPA";

                    // create account in CAIB
                    $account = new Account();
                    $account->client_id = $client->id;
                    $account->type = $client->is_bank ? 'bank' : $client->type;
                    $account->title = $account_title;
                    $account->account_number = $bank_account->accountNumber;
                    $account->account_currency = Common::getSEPACurrencyIdByShortCode($bank_account->currency);
                    $account->is_active = (Auth::user()->manager_id > 0) ? 0 : 1;
                    // $account->openpayd_account_id = $new_openpayd_client->id;
                    $account->is_crypto = false;
                    $account->created_by = Auth::user()->id;

                    if ($client->type == "company") {
                        $account->company_id = $client->company->id;
                    } else {
                        $account->individual_id = $client->individual->id;
                    }
                    $account->save();

                    // store openpayd accounts.
                    $open_payd_bank_account = new OpenPaydBankAccount([
                        'openpayd_linked_client_id' => $openpayd_client->id,
                        'account_id' => $account->id,
                        'client_id' => $openpayd_client->client_id, // Openpayd linkedId client id.
                        'openpayd_account_id' => $op_account->id, // Openpayd account encrypted id.
                        'currency' => $bank_account->currency,
                        'status' => $bank_account->status,
                        'internal_account_id' => $bank_account->internalAccountId,
                        'bank_country' => $bank_account->bankCountry,
                        'bank_address' => $bank_account->bankAddress,
                        'iban' => $bank_account->iban,
                        'bic' => $bank_account->bic,
                        'account_number' => $bank_account->accountNumber,
                        'bank_name' => $bank_account->bankName,
                        'bank_account_holder_name' => $bank_account->bankAccountHolderName,
                        'provider' => $bank_account->provider,
                        'payment_type' => $bank_account->paymentType,
                        'domestic' => $bank_account->domestic,
                        'routing_code_entries' => json_encode($bank_account->routingCodeEntries),
                        'pay_in_reference' => $bank_account->payInReference,
                    ]);

                    $open_payd_bank_account->save();

                    $account->openpayd_account_id = $open_payd_bank_account->id;
                    $account->save();
                }
                DB::commit();
            }

            // Sync client openpayd beneficiary.
            $this->syncBeneficiary($account_holder_id, $client, $openpayd_api, $openpayd_client);

            return redirect()->back()->with('success', "Data sync successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'internal server error, Please try again.');
        }
    }

    private function syncBeneficiary($account_holder_id, $client, $openpayd_api, $openpayd_client)
    {
        // getting openpayd client beneficiaries
        $bank_beneficiaries = $openpayd_api->getClientBankBeneficiaries($account_holder_id)->content;
        try {

            if (!empty($bank_beneficiaries)) {
                foreach ($bank_beneficiaries as $bank_beneficiary) {

                    $is_client_beneficiary_exists = OpenpaydBankBeneficiary::where('beneficiary_id', $bank_beneficiary->beneficiaryId)->first();
                    if (empty($is_client_beneficiary_exists)) {
                        $user = User::where('client_id', $client->id)->first();
                        $account_number = $bank_beneficiary->accountNumber ?? $this->getAccountNumberFromIBAN($bank_beneficiary->iban);

                        DB::beginTransaction();

                        // add new beneficiary
                        $ben = new ClientBeneficiary();
                        $ben->user_id = $user->id;
                        $ben->name = $bank_beneficiary->friendlyName;
                        $ben->client_id = $client->id;
                        $ben->account_number = $account_number;
                        $ben->iban = $bank_beneficiary->iban;
                        $ben->address = $bank_beneficiary->bankAddress;
                        $ben->bank_name = $bank_beneficiary->bankName;
                        $ben->swift_code = $bank_beneficiary->bic;
                        $ben->bank_address = $bank_beneficiary->bankAddress;
                        $ben->account_currency = Common::getSEPACurrencyIdByShortCode($bank_beneficiary->currency);; // get sepa currency id. (EUR)
                        $ben->beneficiary_country = Common::getCurrencyIdByISO2Code($bank_beneficiary->beneficiaryCountry);
                        $ben->bank_account_country = Common::getCurrencyIdByISO2Code($bank_beneficiary->bankAccountCountry);
                        $ben->status = "Approved"; //Changed this to Approved on FAIB request.
                        $ben->created_by = $user->id;
                        $ben->save();

                        $beneficiaryData = [
                            'user_id' => auth()->id(), // or set the relevant user ID
                            'linked_client_id' => $openpayd_client['linked_client_id'] ?? null, // Assign appropriate value
                            'account_holder_id' => $account_holder_id,
                            'beneficiary_id' => $bank_beneficiary->beneficiaryId,
                            'bank_beneficiary_id' => $bank_beneficiary->id,
                            'external_id' => $bank_beneficiary->externalId,
                            'status' => $bank_beneficiary->status,
                            'friendly_name' => $bank_beneficiary->friendlyName,
                            'beneficiary_first_name' => $bank_beneficiary->beneficiaryFirstName,
                            'beneficiary_last_name' => $bank_beneficiary->beneficiaryLastName,
                            'beneficiary_type' => $bank_beneficiary->beneficiaryType,
                            'beneficiary_country' => $bank_beneficiary->beneficiaryCountry,
                            'beneficiary_state' => $bank_beneficiary->beneficiaryState,
                            'beneficiary_postal_code' => $bank_beneficiary->beneficiaryPostalCode,
                            'beneficiary_city' => $bank_beneficiary->beneficiaryCity,
                            'beneficiary_address_line' => $bank_beneficiary->beneficiaryAddressLine,
                            'account_number' => null,
                            'iban' => $bank_beneficiary->iban,
                            'bic' => $bank_beneficiary->bic,
                            'currency' => $bank_beneficiary->currency,
                            'bank_name' => $bank_beneficiary->bankName,
                            'bank_address' => $bank_beneficiary->bankAddress,
                            'bank_account_country' => $bank_beneficiary->bankAccountCountry,
                            'bank_account_holder_name' => $bank_beneficiary->bankAccountHolderName,
                            'company_name' => $bank_beneficiary->companyName,
                            'bank_account_alias' => $bank_beneficiary->bankAccountAlias,
                            'document_id' => $bank_beneficiary->documentId,
                            'intermediary_bank_account_country' => $bank_beneficiary->intermediaryBankAccountCountry,
                            'intermediary_name' => $bank_beneficiary->intermediaryName,
                            'intermediary_town_name' => $bank_beneficiary->intermediaryTownName,
                            'intermediary_postal_code' => $bank_beneficiary->intermediaryPostalCode,
                            'bban' => $bank_beneficiary->bban,
                            'payment_types' => $bank_beneficiary->paymentTypes,
                            'bank_routing_codes' => $bank_beneficiary->bankRoutingCodes,
                            'class' => $bank_beneficiary->class,
                            'tax_id' => $bank_beneficiary->taxId,
                            'phone_number' => $bank_beneficiary->phoneNumber,
                            'title' => $bank_beneficiary->title,
                            'beneficiary_birth_date' => $bank_beneficiary->beneficiaryBirthDate,
                            'bank_account_type' => $bank_beneficiary->bankAccountType,
                            'uploaded_kyc_document' => $bank_beneficiary->uploadedKycDocument,
                            'metadata' => $bank_beneficiary->metadata,
                            'parent_beneficiary_id' => $ben->id,
                        ];
                        OpenpaydBankBeneficiary::create($beneficiaryData);
                        DB::commit();
                    }
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function syncBeneficiaryRequiredDetails(Request $request)
    {
        $request->validate([
            'currency_id' => 'required',
            'country_id' => 'required',
            'beneficiary_type' => 'required',
        ]);
        if (!Common::isModuleEnabled('OpenPayd')) {
            return redirect(url()->previous())->with('error', 'OpenPayd Module is disabled')->with('formnumber', 3);
        }

        $country_code = Country::where('id', $request->get('country_id'))->value('iso');
        $currency_short = Currency::where('id', $request->get('currency_id'))->value('short');
        $beneficiary_type = $request->get('beneficiary_type');

        $openpayd_api = new OpenpaydApis();
        $required_details = $openpayd_api->getBeneficiaryRequiredDetails($country_code, $currency_short, $beneficiary_type);

        $previous_entry = BeneficiaryRequiredFields::where('country_id', $request->get('country_id'))
            ->where('currency_id', $request->get('currency_id'))
            ->where('beneficiary_type', $beneficiary_type)
            ->first();

        // Store/update required details in DB if needed.
        $beneficiaryField = $previous_entry ?? new BeneficiaryRequiredFields();
        $beneficiaryField->country_id = $request->get('country_id');
        $beneficiaryField->currency_id = $request->get('currency_id');
        $beneficiaryField->beneficiary_type = $request->get('beneficiary_type');
        $beneficiaryField->required_fields = json_encode($required_details);
        $beneficiaryField->save();

        if ($required_details && empty($required_details->errorCode)) {
            return redirect()->back()->with('success', 'Required details fetched successfully.');
        } else {
            return redirect()->back()->with('error', 'Failed to fetch required details.');
        }
    }

    public function syncAllBeneficiaryRequiredDetails(Request $request)
    {
        $request->validate([
            'sync_currency_id' => 'required',
        ]);

        if (!Common::isModuleEnabled('OpenPayd')) {
            return redirect(url()->previous())->with('error', 'OpenPayd Module is disabled')->with('formnumber', 3);
        }

        $currency_code = Currency::where('id', $request->get('sync_currency_id'))->value('short');
        $countries = Country::where('sepa_supported', true)->pluck('iso', 'id')->toArray();
        $beneficiary_types = ['RETAIL', 'CORPORATE'];

        try {

            $openpayd_api = new OpenpaydApis();

            foreach ($countries as $country_id => $country_code) {
                foreach ($beneficiary_types as $beneficiary_type) {
                    $previous_entry = BeneficiaryRequiredFields::where('country_id', $country_id)
                        ->where('currency_id', $request->get('sync_currency_id'))
                        ->where('beneficiary_type', $beneficiary_type)
                        ->first();

                    if (($previous_entry && !empty($previous_entry->required_fields))) {
                        continue; // Skip if entry exists with required fields
                    }

                    $required_details = $openpayd_api->getBeneficiaryRequiredDetails(
                        $country_code,
                        $currency_code,
                        $beneficiary_type
                    );

                    // Store/update required details in DB if needed.
                    $beneficiaryField = $previous_entry ?? new BeneficiaryRequiredFields();
                    $beneficiaryField->country_id = $country_id;
                    $beneficiaryField->currency_id = $request->get('sync_currency_id');
                    $beneficiaryField->beneficiary_type = $beneficiary_type;
                    $beneficiaryField->required_fields = json_encode($required_details);
                    $beneficiaryField->save();
                }
            }

            if ($required_details && empty($required_details->errorCode)) {
                return redirect()->back()->with('success', 'Required details fetched successfully.');
            } else {
                return redirect()->back()->with('error', 'Failed to fetch required details.');
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'An error occurred during synchronization: ' . $e->getMessage());
        }
    }

    private function getAccountNumberFromIBAN($iban)
    {
        // Remove spaces just in case
        $iban = str_replace(' ', '', $iban);

        // UK IBANs have a fixed structure, and the account number is the last 8 digits
        return substr($iban, -8);
    }
}
