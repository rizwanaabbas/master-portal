<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ClientHelper;
use App\Helpers\Common;
use App\Helpers\LorumApis;
use App\Models\Account;
use App\Models\Client;
use App\Models\VirtualBankAccount;
use App\Models\VirtualBankBeneficiary;
use App\Models\VirtualBankLinkedClient;
use App\Models\VirtualBankTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class LorumController extends Controller
{
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

        if (!Common::isModuleEnabled('Lorum')) {
            return redirect(url()->previous())->with('error', 'Lorum Module is disabled')->with('formnumber', 3);
        }

        // try {
        if (!empty($request->get("account_id"))) {

            //foreach ($request->get("lorum_currency") as $currency) {
            $account = Account::find($request->get("account_id"));
            $client = ClientHelper::getClientByAccountId($account->id);
            $currency = $account->currency;
            if (!empty($currency))
                $currency_code = $currency->short;
            else
                return redirect(url()->previous())->with('error', 'Account currency not found')->with('formnumber', 3);

            $exists = VirtualBankAccount::where('client_id', $client->id)->where('currency', $currency->id)->where('provider_name', 'lorum')->first();

            if ($exists) {
                return redirect()->back()->with('error', "$currency_code currency account already exits.");
            }

            $LorumApis = new LorumApis();
            $lorum_client = $LorumApis->storeLorumdClient($client, $account);

            if ($lorum_client && !$lorum_client->error) {

                if (!empty($lorum_client) && $lorum_client->status !== "ACTIVE") {
                    $messages = 'Lorum Client created with Status: ' . $lorum_client->status . '. Please wait for the onboarding process to complete.';
                    return redirect(url()->previous())->with('warning', $messages);
                }

                $bank_account = $LorumApis->storeLorumAccount($account, $lorum_client);
                //dd($bank_account);
                if (!empty($bank_account->errorCode) || !empty($bank_account->error)) {

                    if ($bank_account->error === true && $bank_account->status_code === 404) {
                        $messages = !empty($bank_account->message) ? $bank_account->message : 'The IBAN creation request was initiated, Refresh after some time to see the IBAN details.';
                        return redirect(url()->previous())->with('warning', $messages);
                    } else {
                        $messages = !empty($bank_account->message) ? $bank_account->message : 'Something went wrong, please try again later.';
                    }

                    return redirect(url()->previous())->with('error', $messages);
                }
                //dd($bank_account);
                //$account->account_number = $bank_account->account_number;
                $account->virtual_bank_account_id = $bank_account->id;
                $account->save();

                // Async background job
                $this->getLorumAccountDetails($bank_account->virtual_bank_account_id);

                return redirect(url()->previous())->with('success', "IBAN has been created successfully.");
            } else if (!empty($lorum_client->message)) {

                return redirect(url()->previous())->with('error', "Lorum Error: " . $lorum_client->message);
            }
        }
        // } catch (Exception $e) {
        //     Log::error('Error creating IBAN: ' . $e->getMessage());
        //     return redirect(url()->previous())->with('error', 'Error creating IBAN: ' . $e->getMessage());
        // }
    }

    public function accountConfirmationLetter($account_id)
    {
        if (!$account_id) {
            return redirect()->back()->with('error', 'Account not found.');
        }

        try {
            $LorumApis = new LorumApis();

            // This returns Illuminate\Http\Response with PDF binary
            $response = $LorumApis->accountConfirmationLetter($account_id);

            // Safety check
            if (!$response instanceof \Illuminate\Http\Response || $response->getStatusCode() !== 200) {
                return redirect()->back()->with('error', 'Failed to retrieve confirmation letter.');
            }

            $pdfContent = $response->getContent();
           
            if (empty($pdfContent)) {
                return redirect()->back()->with('error', 'Empty confirmation letter received.');
            }
            $this->storeLorumAccountDetails($account_id, $pdfContent);
           // die();
            // test code for viewing the account confirmation letter in browser instead of downloading
            // $service = app(\App\Services\PdfAccountParserService::class);
            // $account_info = $service->parse($pdfContent, true);
            //end of test code for account confirmation letter in browser instead of downloading
            return response()->make($pdfContent, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Account_Confirmation_Letter_' . $account_id . '.pdf"',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error fetching account confirmation letter', [
                'account_id' => $account_id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with(
                'error',
                'Error fetching account confirmation letter. Please try again later.'
            );
        }
    }

    public function createOutboundPayment($transaction)
    {
        // Check if transaction is valid
        $transaction_method = $transaction->method;

        // Check if Beneficiary exists and all the required fields are present
        if (strtolower($transaction_method->name) === 'wire') {
            if (empty($transaction->beneficiary->account_number) || empty($transaction->beneficiary->swift_code) || empty($transaction->beneficiary->bank_address)) {
                return (object)['error' => true, 'message' => 'Beneficiary details are incomplete for Wire transfer.'];
            }
        } else {
            if (empty($transaction->beneficiary->iban) || empty($transaction->beneficiary->swift_code) || empty($transaction->beneficiary->bank_address)) {
                return (object)['error' => true, 'message' => 'Beneficiary details are incomplete for IBAN transfer.'];
            }
        }

        $beneficiary = $transaction->beneficiary;
        $beneficiary_address = $beneficiary->bank_address;
        $address_line_1 = '';
        $address_line_2 = $beneficiary->bank_city;
        $max_length = 35;
        $address = trim($beneficiary_address);

        if (strlen($address) > $max_length) {
            // Find last space before 35th character
            $breakPos = strrpos(substr($address, 0, $max_length + 1), ' ');

            // If no space found, hard cut (fallback)
            if ($breakPos === false) {
                $breakPos = $max_length;
            }

            $address_line_1 = substr($address, 0, $breakPos);
            $address_line_2 = ltrim(substr($address, $breakPos)) . ' ' . $address_line_2;
        } else {
            $address_line_1 = $address;
        }

        $beneficiary_type = $beneficiary->beneficiary_type == "RETAIL" ? "individual" : "business";

        $LorumApis = new LorumApis();

        // if the Trafer to Primary account is enabled.
        if ($LorumApis->process_payout_from_child) {
            $lorum_child_account_id = $transaction->account->bankAccount->virtual_bank_account_id;

            // First Move Funds from master to child account
            $lorum_sweep_response = $LorumApis->manualLorumSweepTransaction($transaction, $lorum_child_account_id);
            if ($lorum_sweep_response->status === false) {
                return (object)['error' => true, 'message' => $lorum_sweep_response->message ?? 'Failed to sweep funds to child account.'];
            }

             // Then use the child account for making the payment
             $lorum_account_id = $lorum_child_account_id;
             
        } else {
            $lorum_account_id = $LorumApis->getPrimaryAccountByCurrency($transaction->currency->short);
        }

        $transfer_type = null;
        // Lorum docs: This is the amount in minor currency. For example, AED 1.00 will be 100.
        $transaction_amount = Common::toMinorAmount($transaction->amount);

        if (strtolower($transaction_method->name) === 'wire') {
            $bank_beneficiary = $beneficiary->virtualBankBeneficiary;

            if ($bank_beneficiary && $bank_beneficiary->bank_routing_codes) {
                $codes = json_decode($bank_beneficiary->bank_routing_codes);
                $firstCode = is_array($codes) ? ($codes[0] ?? null) : $codes;
                $bank_routing_number = $firstCode->routingCodeValue ?? null;
            }

            $transfer_type = [
                'wire' => [
                    'account_number' => $beneficiary->account_number,
                    'routing_number' => $bank_routing_number,
                ],
            ];
        } else {
            $transfer_type = [
                'iban' => $beneficiary->iban,
            ];
        }

        // Lorum Outbound Payment Payload
        $payload = [
            // 'amount' => $transaction->amount * 100, 
            'amount' => $transaction_amount,
            'beneficiary' => [
                'account_holder_name' => $beneficiary->name,
                'account_identifier' => $transfer_type,
                'address' => [
                    'country_code' => $beneficiary->country->iso,
                    'line_1' => $address_line_1,
                    'line_2' => $address_line_2,
                    'line_3' => $beneficiary->postal_code ?? null,
                ],
                'bank' => ['code' => $beneficiary->swift_code],
                'type' => $beneficiary_type,
            ],
            'external_reference' => $transaction->remarks,
            'purpose' => 'none',
        ];

        // $headers = ['Idempotency-Key' => md5($transaction->id . time())];

        $idempotency_key = md5($transaction->id);
        $headers = ['Idempotency-Key' => $idempotency_key];
        $payment_response = $LorumApis->createOutboundPaymentApi($payload, $lorum_account_id, $headers);

        if ($payment_response && isset($payment_response->error)) {
            return (object)['error' => true, 'message' => 'Lorum Payment Error: ' . ($payment_response->message ?? 'Unknown error')];
        }

        // Get the Lorum Transaction details
        $transaction_details = $LorumApis->getTransactionById($payment_response["transaction_id"]);
        $transaction_details = json_decode(json_encode($transaction_details));
        
        if (!empty($transaction_details) && empty($transaction_details->id)) {

            $transaction_data = (object) [
                'id' => $payment_response["transaction_id"] ?? null,
                'account_id' => $lorum_account_id,
                'direction' => 'outbound',
                'state' => 'Draft',
                'created_at' => Carbon::now()->toDateTimeString(),
                'remitter' => [
                    'identifier' => [
                        'wire' => $payload['beneficiary']['account_identifier']['wire'] ?? null,
                        'iban' => $payload['beneficiary']['account_identifier']['iban'] ?? null,
                    ]
                ],
            ];

            return $transaction_data ?? (object)['error' => true, 'message' => 'Lorum Payment Error: ' . ($payment_response->message ?? 'Unknown error')];
        }

        log::info("===================================== transaction_details =====================================");
        log::info(json_encode($transaction_details));
        log::info("===================================== transaction_details =====================================");

        $transaction_details->idempotency_key = $idempotency_key;
        $data = json_encode($transaction_details);
        $this->storeApiResponseData($transaction_details->id, $data, 'Lorum');
        return $transaction_details;
    }

    public function storeLorumTransaction($data, $trn_request_id = null, $acc_trn_id = null)
    {
        $paymentType = null;

        if (isset($data->remitter, $data->remitter->identifier)) {
            if (isset($data->remitter->identifier->wire)) {
                $paymentType = 'WIRE';
            } elseif (isset($data->remitter->identifier->iban)) {
                $paymentType = 'IBAN';
            }
        }

        $transaction_type_mapping = [
            "outbound" => "PAYOUT",
            "inbound" => "PAYIN",
        ];

        // Save the transaction to the database
        $transaction = new VirtualBankTransaction();
        $transaction->parent_transaction_id = $acc_trn_id;
        $transaction->trn_id = $trn_request_id;
        $transaction->transaction_id = $data->id;
        $transaction->account_id = $data->account_id;
        $transaction->transaction_created_date = Carbon::parse($data->created_at)->format('Y-m-d H:i:s');
        $transaction->virtual_bank_transaction_id = $data->id;
        $transaction->payment_type = $paymentType;
        $transaction->type = $transaction_type_mapping[strtolower($data->direction)];
        $transaction->provider_name = 'Lorum';
        $transaction->status = $data->state === "Executed" ? "Approved" : $data->state;
        $transaction->response = json_encode($data); // Store full response as JSON
        $transaction->save();
        return $transaction;
    }

    public function storeApiResponseData($transaction_id, $data, $crypto_system)
    {
        DB::table('crypto_transactions_log')->insert([
            'transaction_id' => $transaction_id,
            'details' => $data,
            'crypto_system' => $crypto_system,
            'source' => 'admin',
            'date_created' => date('Y-m-d H:i:s', time())
        ]);
    }

    public function createLorumBeneficiary(Request $request, $beneficiary)
    {
        try {
            $payment_type = $beneficiary->currency->short === "AED" ? ["IBAN"] : ["WIRE"];

            // Lorum beneficiary
            $virtual_bank_beneficiary = new VirtualBankBeneficiary();
            $virtual_bank_beneficiary->beneficiary_id = $beneficiary->id;

            if ($request->get('routing_number')) {
                $virtual_bank_beneficiary->bank_routing_codes = json_encode([
                    'routingCodeKey' => 'ROUTING_NUMBER',
                    'routingCodeValue' => $request->get('routing_number'),
                ]);
            }

            $beneficiary_type = $request->get("beneficiary_type") == "individual" ? "RETAIL" : "CORPORATE";

            $virtual_bank_beneficiary->parent_beneficiary_id = $beneficiary->id;
            $virtual_bank_beneficiary->user_id = $request->user()->id;
            $virtual_bank_beneficiary->friendly_name = $request->get("name");
            $virtual_bank_beneficiary->account_number = $request->get("account_number");
            $virtual_bank_beneficiary->bic = $request->get("swift_code");
            $virtual_bank_beneficiary->currency = $beneficiary->currency->short;
            $virtual_bank_beneficiary->provider_name = 'lorum';
            $virtual_bank_beneficiary->status = 'Approved';
            $virtual_bank_beneficiary->bank_account_country = $beneficiary->country->iso;
            $virtual_bank_beneficiary->beneficiary_type = $beneficiary_type;
            $virtual_bank_beneficiary->payment_types = $payment_type;
            $virtual_bank_beneficiary->save();

            return $virtual_bank_beneficiary;
        } catch (\Throwable $e) {
            Log::error('Error creating Lorum beneficiary: ' . $e->getMessage());
            return (object)['error' => true, 'message' => 'Error creating Lorum beneficiary: ' . $e->getMessage()];
        }
    }

    public function getSweepTransactionByTrnId($trn_id)
    {
        $lorum_transaction = VirtualBankTransaction::where('parent_transaction_id', $trn_id)->first();

        if (empty($lorum_transaction)) {
            return (object) ['errorCode' => 'TRANSACTION_NOT_FOUND', 'message' => 'Transaction not found.'];
        }

        $transaction_log = DB::table('crypto_transactions_log')->where('transaction_id', $lorum_transaction->virtual_bank_transaction_id)->get();

        foreach ($transaction_log as $log) {
            $log_details = json_decode($log->details);
            if ($log_details->type === 'SWEEP_TRANSFER' && $log_details->internal_transfer_created->external_id === $lorum_transaction->virtual_bank_transaction_id) {
                return $log_details;
            }
        }

        return null;
    }

    public function getLorumAccountDetails($account_id)
    {
        if (empty($account_id)) {
            return redirect()->back()->with('error', 'Account not found.');
        }

        $tempPath = null; // <--- declare here

        try {
            $LorumApis = new LorumApis();
            $response = $LorumApis->accountConfirmationLetter($account_id);

            if (!$response || $response->getStatusCode() !== 200) {
                throw new \Exception('Invalid response from Lorum API');
            }

            log::info("Extraction account details from account confirmation letter.");

            $pdfContent = $response->getContent();
            if (empty($pdfContent)) {
                throw new \Exception('Empty PDF content');
            }

           
            $service = app(\App\Services\PdfAccountParserService::class);
            $account_info = $service->parse($pdfContent, true);
            // Fetch account safely
            $virtual_account = VirtualBankAccount::where('virtual_bank_account_id', $account_id)->first();
            
            if (!$virtual_account && !empty($account_info)) {
                throw new \Exception('Virtual account not found, Ofr Pdf content is empty');
            }

           // Only update if value exists
            if (!empty($account_info['swift_code'])) {
                $virtual_account->bic = $account_info['swift_code'];
            }
            if (!empty($account_info['bank_address'])) {
                $virtual_account->bank_address = $account_info['bank_address'];
            }
            if (!empty($account_info['bank_name'])) {
                $virtual_account->bank_name = $account_info['bank_name'];
            }
            $virtual_account['account_info'] = json_encode($account_info);
            $virtual_account->save();

            // return array_filter($data);

        } catch (\Throwable $e) {
            Log::error('Error fetching account confirmation letter', [
                'account_id' => $account_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function storeLorumAccountDetails($account_id, $pdfContent)
    {
        if (empty($account_id)) {
            return redirect()->back()->with('error', 'Account not found.');
        }

        $tempPath = null; // <--- declare here

        try {

            log::info("Extraction account details from account confirmation letter.");

            if (empty($pdfContent)) {
                throw new \Exception('Empty PDF content');
            }

            // Temp file (Laravel-safe)
            $service = app(\App\Services\PdfAccountParserService::class);
            $account_info = $service->parse($pdfContent, true);
            //$account_info = json_decode($account_info_data, true);
            // Fetch account safely
            $virtual_account = VirtualBankAccount::where('virtual_bank_account_id', $account_id)->first();
            if (!$virtual_account && !empty($account_info)) {
                throw new \Exception('Virtual account not found, Ofr Pdf content is empty');
            }

            // Only update if value exists
            if (!empty($account_info['swift_code'])) {
                $virtual_account->bic = $account_info['swift_code'];
            }
            if (!empty($account_info['bank_address'])) {
                $virtual_account->bank_address = $account_info['bank_address'];
            }
            if (!empty($account_info['bank_name'])) {
                $virtual_account->bank_name = $account_info['bank_name'];
            }
           
            $virtual_account['account_info'] = json_encode($account_info);
            //dd($account_info['account_info'] );
            $virtual_account->save();

            // return array_filter($data);

        } catch (\Throwable $e) {
            Log::error('Error fetching account confirmation letter', [
                'account_id' => $account_id,
                'error' => $e->getMessage(),
            ]);

            // Only try to delete if $tempPath exists
            // if ($tempPath && file_exists($tempPath)) {
            //     @unlink($tempPath);
            // }

            // return null;
        }
    }


    // ----------------------- Lorum Sync Scripts -----------------------

    public function lorumSyncData(Request $request)
    {
        $lorumApi = new LorumApis();
        $lorumClients = $lorumApi->getAllCustomer()['customers'] ?? [];

        // Preload local data (FAST lookups)
        $linkedClientIds = array_flip(VirtualBankLinkedClient::pluck('linked_client_id')->toArray());
        $clientIds = array_flip(Client::pluck('id')->toArray());

        $outOfSyncClients = [];
        $notInLocalClients = [];

        foreach ($lorumClients as $lorumClient) {

            $lorumClientId = $lorumClient["id"];
            $clientId = $lorumClient["reference"];

            $existsInLinked = isset($linkedClientIds[$lorumClientId]);
            $existsInClient = isset($clientIds[$clientId]);

            // Skip completely unknown
            if (!$existsInLinked && !$existsInClient) {
                $notInLocalClients[] = $lorumClient;
                continue;
            }

            // Out of sync client
            if (!$existsInLinked && $existsInClient) {
                $outOfSyncClients[] = $lorumClient;
            }
        }

        return view("settings.lorum.sync-data", [
            'lorum_clients' => $lorumClients,
            'out_of_sync_clients' => $outOfSyncClients,
            'not_in_local_clients' => $notInLocalClients
        ]);
    }

    public function showClientDetails($lorumCustomerId)
    {
        $lorumApi = new LorumApis();
        $is_client_synced = VirtualBankLinkedClient::where('linked_client_id', $lorumCustomerId)->first();
        $local_bank_accounts = [];


        // Get Customers/Accounts from lorum.
        $customers = $lorumApi->getCustomerById($lorumCustomerId);
        $virtual_accounts = $lorumApi->getClientAccounts($lorumCustomerId)['results'] ?? [];

        // Get all synced virtual account IDs
        $virtual_account_ids = array_flip(VirtualBankAccount::pluck('virtual_bank_account_id')->toArray());
        $customer_reference = $customers['reference'] ?? null;

        // Get local client (single query)
        $local_client = $customer_reference ? Client::find($customer_reference) : null;

        if (!empty($is_client_synced) && empty($local_client)) {
            $local_client = $is_client_synced->client;
        }

        // if ($local_client) {
        //     $local_bank_accounts = $local_client->virtualBankAccounts->where('provider_name', 'lorum');
        // }

        // Clients list (fallback to all if not found)
        $clients = $local_client ? collect([$local_client]) : Client::all();

        // Account sync status
        foreach ($virtual_accounts as &$account) {
            $account['is_synced'] = isset($virtual_account_ids[$account['id']]);
        }

        return view('settings.lorum.client-details', compact(
            'customers', 
            'virtual_accounts', 
            'clients', 
            'local_client', 
            'is_client_synced', 
            // 'local_bank_accounts'
        ));
    }

    public function syncLorumClient(Request $request)
    {
        $lorumClientId = $request->get('lorum_customer_id');
        $clientId = $request->get('client_id');

        $client = Client::find($clientId);
        if (!$client) {
            return redirect()->back()->with('error', 
            'Invalid client id.');
        }

        $lorumApi = new LorumApis();

        try {
            // Check if already linked
            $existing = VirtualBankLinkedClient::where('linked_client_id', $lorumClientId)->first();            
            if (!empty($existing)) {
                return redirect()->back()->with('warning', 'Client already synced.');
            }

            // Get Lorum Client
            $lorumClient = $lorumApi->getCustomerById($lorumClientId);
            if (empty($lorumClient)) {
                return redirect()->back()->with('error', 'Lorum client not found.');
            }

            Log::info('Lorum Client: ' . json_encode($lorumClient));

            $lorumClientStatus = $lorumClient['approval_status'] === "approved" || "auto_approved" ? "ACTIVE" : "PENDING";

            // Create mapping
            $linkedClient = new VirtualBankLinkedClient();
            $linkedClient->client_id = $clientId;
            $linkedClient->linked_client_id = $lorumClient['id'];
            $linkedClient->status = $lorumClientStatus;
            $linkedClient->kyc_status = $lorumClientStatus;
            $linkedClient->country = $lorumClient['address']['country_code'] ?? null;
            $linkedClient->response_data = json_encode($lorumClient);
            $linkedClient->provider_name = 'lorum';

            $linkedClient->save();

            return redirect()->back()->with('success', 'Client synced successfully.');
        } catch (\Exception $e) {
            Log::error('Lorum Client Sync Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Internal server error.');
        }
    }

    public function syncLorumAccounts(Request $request)
    {
        $lorumCustomerId = $request->get('lorum_customer_id');
        $lorumApi = new LorumApis();

        try {
            $linkedClient = VirtualBankLinkedClient::where('linked_client_id', $lorumCustomerId)->first();

            if (!$linkedClient) {
                return redirect()->back()->with('error', 'Client not synced yet.');
            }

            // Get Lorum Accounts            
            $response = $lorumApi->getClientAccounts($lorumCustomerId);
            $accounts = $response['results'] ?? [];

            // Preload for fast lookup
            $existingVirtualAccounts = array_flip(VirtualBankAccount::pluck('virtual_bank_account_id')->toArray());
            $sync_account_error = [];
            
            foreach ($accounts as $accountData) {

                $lorumAccountId = $accountData['id'];
                $localAccountId = $accountData['external_id'];

                // Skip already synced
                if (isset($existingVirtualAccounts[$lorumAccountId])) {
                    continue;
                }

                // Only sync if local account exists
                $account = Account::find($localAccountId);
                if (!$account) {
                     $sync_account_error[] = [
                        'lorum_account_id' => $lorumAccountId,
                        'local_account_id' => $localAccountId,
                        'message' => 'Account not found locally'
                    ];
                    continue;
                }

                DB::beginTransaction();

                // Extract all possible values safely
                $defaultDetails = $accountData['default_details'] ?? [];
                $iban           = $defaultDetails['iban'] ?? null;
                $wire           = $defaultDetails['wire'] ?? null;

                $accountNumber = null;
                $routingEntries = null;

                // Unified handling
                if ($iban) {

                    // IBAN-based account
                    $accountNumber  = $defaultDetails['account_number'] ?? 'N/A';
                    $routingEntries = $defaultDetails['routing_number'] ?? 'N/A';
                } elseif ($wire) {

                    // Wire-based account
                    $accountNumber = $wire['account_number'] ?? null;

                    if (!empty($wire['routing_number'])) {
                        $routingEntries = json_encode([
                            [
                                "routingCodeKey"   => "ROUTING_NUMBER",
                                "routingCodeValue" => $wire['routing_number']
                            ]
                        ]);
                    }

                    $iban = 'N/A';
                }
                
                // Store Virtual Account
                $virtual = VirtualBankAccount::create([
                    'linked_client_id'         => $linkedClient->id,
                    'account_id'               => $account->id,
                    'client_id'                => $account->client_id,
                    'virtual_bank_account_id'  => $accountData['id'],
                    'internal_account_id'      => $accountData['id'] ?? null,
                    'currency'                 => $accountData['currency'] ?? null,
                    'account_number'           => $accountNumber,
                    'routing_code_entries'     => $routingEntries,
                    'currency_id'              => $account->currency->id ?? null,
                    'status'                   => $accountData['status'] ?? null,
                    'iban'                     => $iban,
                    'bank_country'             => ($iban && $iban !== 'N/A') ? substr($iban, 0, 2) : "N/A",
                    'bank_name'                => 'Lorum',
                    'bank_account_holder_name' => $accountData['account_owner'] ?? 'N/A',
                    'provider_name'            => 'lorum',
                    'bank_address'             => 'N/A',
                    'bic'                      => 'N/A',
                    'response_data'            => json_encode($accountData),
                ]);

                // Link to Account
                $account->virtual_bank_account_id = $virtual->id;
                $account->save();

                DB::commit();
            }

            if (!empty($sync_account_error)) {
                $warnings = collect($sync_account_error)->map(function ($error) {
                    return "Local Account ID {$error['local_account_id']}, does not exist locally";
                })->toArray();
    
                return redirect()->back()->with('warning', $warnings);
            }

            return redirect()->back()->with('success', 'Accounts synced successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lorum Account Sync Error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Internal server error.');
        }
    }
    
}
