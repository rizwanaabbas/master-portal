<?php

namespace App\Http\Controllers;

use App\Helpers\ChangeLogger;
use App\Helpers\ClientHelper;
use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Helpers\FileFirewall;
use App\Helpers\S3Helper;
use App\Models\Account;
use App\Models\Client;
use App\Models\ClientDirector;
use App\Models\ClientAccount;
use App\Models\ClientCompany;
use App\Models\ClientCompanyAccount;
use App\Models\ClientCompanyContact;
use App\Models\ClientCompanyFile;
use App\Models\ClientDraftCurrency;
use App\Models\ClientIndividualEmployer;
use App\Models\ClientFileConfig;
use App\Models\ClientIndividual;
use App\Models\ClientIndividualFile;
use App\Models\ClientUser;
use App\Models\CompanyType;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Unit;
use App\Models\ModelChange;
use App\Models\OpenPaydBankAccount;
use App\Models\OpenPaydLinkedClients;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use App\Exports\ExportClientProfilesReport;
use App\Helpers\FingateHelper;
use App\Helpers\IDORHelper;
use App\Helpers\OnboardingFeeHelper;
use App\Models\ClientKYC;
use App\Rules\NotNullAndNotEmptyRule;
use Maatwebsite\Excel\Facades\Excel;
use App\Rules\SupportingDocumentsRule;
use GuzzleHttp\Client as GuzzleHttpClient;
use Illuminate\Support\Facades\Hash;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Psr7\Utils;
use App\Helpers\OnboardingHelper;
use App\Helpers\WorldCheckHelper;
use App\Services\WorldCheckConfigService;
use App\Models\ClientRiskRating;
use App\Helpers\OpenpaydApis;
use App\Helpers\LorumApis;
use App\Helpers\ZenusApis;
use App\Models\CardDetail;
use App\Models\FingateClient;
use App\Models\Sanctions;
use App\Models\SanctionsReport;
use App\Models\OnboardingFeeRecords;
use App\Models\KycRequest;
use App\Models\MobileRequest;
use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Models\NAICSCode;
use App\Models\RiskScoreMatrix;
use App\Models\SOCCode;
use App\Modules\Aml\Controllers\AmlRuleController;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    protected $is_openpayd_enabled = false;
    public function __construct()
    {
        $this->middleware('permission:client list', ['only' => 'index']);
        $this->middleware('permission:add client', ['only' => ['addCompany', 'addIndividual', 'storeCompany', 'storeIndividual']]);
        $this->middleware('permission:edit client', ['only' => 'editCompany', 'editIndividual', 'updateCompany', 'updateCompanyContact', 'updateIndividual', 'updateIndividualEmployer', 'updateIndividualCurrency', 'updateIndividualFiles', 'updateCompanyFiles', 'updateClientRiskRating']);
        $this->middleware('permission:delete client', ['only' => 'deleteClient']);
        $this->middleware('permission:compliance management', ['only' => 'verifyClientKYC']);
        $this->middleware('permission:approve client', ['only' => 'approveClient']);
        $this->middleware('permission:linked users', ['only' => 'userClients']);
        $this->middleware('permission:closed clients list', ['only' => 'closedClients']);

        $this->is_openpayd_enabled = common::isModuleEnabled('OpenPayd');
    }

    public function accordion($accordion_id = '')
    {
        if (!empty($accordion_id))
            session(['accordion_id' => $accordion_id]);

        echo session('accordion_id');
    }

    public function index(Request $request)
    {
        // if ($request->ajax()) {
        $clients = Client::select(
            "clients.id",
            "clients.name",
            "clients.type",
            "clients.created_at",
            "clients.approved_at",
            "clients.status",
            "clients.parent_id",
            "clients.is_bank",
            "clients.introducer",
            "creator.name as creator",
            "approver.name as approver"
        )
            ->where("clients.status", "Approved")
            ->leftJoin("users as creator", "clients.created_by", "=", "creator.id")
            ->leftJoin("users as approver", "clients.approved_by", "=", "approver.id")
            ->orderBy('clients.approved_at', 'desc')
            ->with('parent');

        $perPage = $request->get("per_page");

        if ($request->get('search_string')) {
            $clients->where(function ($query) use ($request) {
                $query->where('clients.name', 'like', '%' . $request->get('search_string') . '%')
                    ->orWhere('clients.type', 'like', '%' . $request->get('search_string') . '%')
                    ->orWhere('creator.name', 'like', '%' . $request->get('search_string') . '%')
                    ->orWhere('approver.name', 'like', '%' . $request->get('search_string') . '%');
            });
        };

        //dd($industries);
        if ($perPage == -1 || ($request->get('search_string') && $clients->count() < 10)) {
            $perPage = $clients->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }
        // return Datatables::of($clients)->make(true);
        // }

        $clients = $clients->paginate($perPage);

        // $clients = [];
        return view('client-management.list', compact('clients', 'request'));
    }

    public function generateClientProfileWiseExcel()
    {
        return Excel::download(new ExportClientProfilesReport, 'client-profiles-report.xlsx');
    }

    public function pendingClients()
    {
        $clients = Client::where("status", "Pending")->orderBy("created_at", "desc")->get();
        return view('client-management.peding-list', compact('clients'));
    }

    public function rejectedClients()
    {
        $clients = Client::where("status", "Rejected")->orderBy("created_at", "desc")->get();
        return view('client-management.rejected-list', compact('clients'));
    }

    public function closedClients()
    {
        $clients = Client::where("status", "Closed")->orderBy("closed_at", "desc")->get();
        return view('client-management.closed-list', compact('clients'));
    }

    public function draftClients()
    {
        $clients = Client::whereIn("status", ["Draft", "In Progress", "Liveness Done", "Liveness Pending"])->orderBy("created_at", "desc")->get();
        return view('client-management.draft-list', compact('clients'));
    }

    public function addCompany($is_bank = 0, $parent_client_id = null)
    {
        Log::info('Method: ' . __METHOD__ . ' | Parent ID: ' . $parent_client_id);
        $currencies = Currency::where("is_active", true)->get();
        $companyTypes = CompanyType::get();
        $industries = RiskScoreMatrix::where('matrix_type', 'industries')
            ->select('id', 'title')  // Only select 'id' and 'title' columns
            ->get();
        $fileTypes = ClientFileConfig::where("type", "company")->get();
        $nationalities = Country::get();

        return view("client-management.new-company", compact('currencies', 'companyTypes', 'fileTypes', 'is_bank', 'parent_client_id', 'nationalities', 'industries'));
    }

    public function storeCompany(Request $request)
    {
        $this->validate($request, [
            'name' => ['required', new NotNullAndNotEmptyRule],
            'registration_date' => 'required',
            'expiry_date' => 'required',
            'company_place' => ['required', new NotNullAndNotEmptyRule],
            'company_type' => 'required',
            'business_reg_number' => ['required', new NotNullAndNotEmptyRule],
            'nature_of_business' => ['required', new NotNullAndNotEmptyRule],
            'business_reg_address' => ['required', new NotNullAndNotEmptyRule],
            'introducer' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->get('sub_accounts_prefix')) {
            $this->validate($request, [
                'sub_accounts_prefix' => ['alpha', 'min:3', 'max:5', 'unique:clients,sub_accounts_prefix']
            ]);
        }

        try {
            // create client
            $client = new Client();

            $client->name = $request->get('name');
            //$client->unit_id = $request->get('unit_id');
            $client->type = "company";
            $client->status = (Auth::user()->manager_id > 0) ? "Draft" : "Pending";
            $client->is_bank = ($request->get('is_bank') == 1) ? 1 : 0;

            $client->send_welcome_email = ($request->get("send_welcome_email")) ? 1 : 0;
            $client->send_ssi = ($request->get("send_ssi")) ? 1 : 0;
            $client->create_user = ($request->get("create_user") || $request->get("parent_client_id")) ? 1 : 0;

            if (Auth::user()->hasRole("super admin")) {
                $client->is_b2b_internal_transfer_allowed = ($request->get("is_b2b_internal_transfer_allowed")) ? 1 : 0;
            }

            $client->created_by = Auth::user()->id;
            $client->sub_accounts_prefix = $request->get('sub_accounts_prefix');
            $client->introducer = $request->get('introducer');

            if ($request->get('parent_client_id')) {
                $client->parent_id = $request->get('parent_client_id');
            }
            //Added for basic admin
            if (empty($client->owner_user_id) || empty($client->unit_id)  && Auth::check()) {
                if (Auth::user()->hasRole("basic admin")) {
                    $client->owner_user_id = Auth::user()->id;
                    if (!empty(Auth::user()->unit->id))
                        $client->unit_id = Auth::user()->unit->id;
                }
            }

            $client->save();


            // create company profile
            $company = new ClientCompany();

            $company->client_id = $client->id;
            $company->registration_date = Carbon::createFromFormat('d/m/Y', $request->get('registration_date'))->format('Y-m-d');
            $company->expiry_date = Carbon::createFromFormat('d/m/Y', $request->get('expiry_date'))->format('Y-m-d');
            $company->company_place = $request->get("company_place");
            $company->company_type = $request->get("company_type");
            $company->business_reg_number = $request->get("business_reg_number");
            $company->business_reg_address = $request->get("business_reg_address");
            $company->nature_of_business = $request->get("nature_of_business");
            $company->agree_declaration = ($request->get("agree_declaration")) ? 1 : 0;
            $company->company_country = $request->get("company_country");
            $company->company_city = $request->get("company_city");
            $company->created_by = Auth::user()->id;


            $company->save();
        } catch (Exception $e) {
            if (strpos($e->getMessage(), "Duplicate")) {
                $message = "Already exists!";
            } else {
                $message = "Internal Server Error, please try again later or contact the Tech team.";
            }
            return redirect(url()->previous())->with('error', $message)->with('parent_client_id', $request->get("parent_client_id"));
        }

        return redirect("/client-management/clients/company/edit/" . $client->id)->with('success', "Company added successfully")->with('parent_client_id', $request->get("parent_client_id"));
    }
    //add main contact as director to display only
    public function addClientAsDirectors($client)
    {
        $client = Client::with('directors')->find($client->id);

        // Add client as director
        $name = '';
        $email = '';
        $passport_expiry = '';
        $passport_number = '';
        $dob = '';
        $gender = '';
        $nationality_id = '';
        if (!empty($client->company->contact->name))
            $name = $client->company->contact->name;
        if (!empty($client->company->contact->email_address))
            $email = $client->company->contact->email_address;

        if (!empty($client->company->contact->passport_expiry))
            $passport_expiry = $client->company->contact->passport_expiry;
        if (!empty($client->company->contact->passport_number))
            $passport_number = $client->company->contact->passport_number;
        if (!empty($client->company->contact->dob))
            $dob = $client->company->contact->dob;
        if (!empty($client->company->contact->gender))
            $gender = $client->company->contact->gender;
        if (!empty($client->company->contact->nationality_id))
            $nationality_id = $client->company->contact->nationality_id;
        if (!empty($client->company->contact)) {
            $client->directors->prepend((object)[
                'id' => 0,
                'client_id' => $client->id,
                'kyc_status' => $client->kyc_status,
                'request_passport' => 0,
                'request_selfie' => 0,
                'is_owner' => true,
                'name' => $name,
                'email' => $email,
                'passport_expiry' => $passport_expiry,
                'passport_number' => $passport_number,
                'dob' => $dob,
                'gender' => $gender,
                'nationality' => $nationality_id,
                'type' => $client->type,
            ]);
        }

        return $client;
    }
    public function editCompany($client_id, $nid = "")
    {
        $client = Client::find($client_id);
        //dd($client);
        $units = Unit::all();
        $currencies = Currency::where("is_active", true);

        // If client has a parent then only list those currencies in which the parent client has accounts.
        if ($client->parent_id) {
            $parentClient = Client::find($client->parent_id);
            $currencies->whereIn('id', $parentClient->clientAccounts->pluck('account_currency')->toArray())
                ->where('is_crypto', 0);
        }

        $currencies = $currencies->get();

        $nationalities = Country::get();
        $clientRiskrating = ClientRiskRating::where('client_id', $client->id)->where('status', 'active')->first();
        $clientScoringData = $clientRiskrating ? json_decode($clientRiskrating->scoring_data, true) : [];
        // dd($nationalities->first() );
        $companyTypes = CompanyType::get();
        // $fileTypes = ClientFileConfig::bank()->get();
        $clientKYC = ClientKYC::where('client_id', $client_id)->whereNull('director_id')->first();
        $clientLinks = [];

        $clients = Client::where('id', '!=', $client_id)
            ->where('parent_id', 0)
            // ->whereNotHas('clients', function ($query) {
            //     $query->where('parent_id', '!=', 0);
            // })
            ->whereDoesntHave('children')
            ->where('clients.status', 'Approved')
            ->get();

        if ($client->users->count() > 0) {
            // $clientLinksRaw = clientUser::where('user_id', $client->users[0]->user->id)->where('client_id', '!=', $client_id);
            // $clients->whereNotIn('id', $clientLinksRaw->select('client_id')->get());
            $clientLinks = $client->children;
        }

        // $clients = $clients->get();

        $changes = self::getChanges($client);

        $fileTypes = $client->is_bank ? ClientFileConfig::bank()->get() : ClientFileConfig::company()->get();

        if ($client->status == "Closed") {
            return view("client-management.view-closed-" . $client->type, compact('currencies', 'fileTypes', 'client'));
        }

        if ($nid > 0) {
            Common::readNotification($nid);
        }

        // check if client is draft
        $isDraft = ClientHelper::isDraftClient($client->id);
        $passport_url = "";


        if ($clientKYC && $clientKYC->passport_copy_url) {
            $passport_url = $clientKYC->passport_copy_url;
        } else {
            $passport_id = ClientFileConfig::where('type', $client->type)->where('identifier', 'passport_copy')->value('id');

            $passport = ClientCompanyFile::where('client_id', $client->id)
                ->where('company_id', $client->company->id)
                ->where('file_id', $passport_id)
                ->first();

            if ($passport && $passport->url) {
                $passport_url = $passport->url;
            } else {
                $passport_url = '';
            }
        }
        $client = $this->addClientAsDirectors($client);
        //dd($client->directors);
        // $passport_url = $passport->url ?? ClientKYC::select('passport_copy_url')->where('client_id', $client->id)->first()['passport_copy_url'];
        $onboardingFeeRecord = OnboardingFeeRecords::where("client_id", $client->id)->first();
        //get the sanctionsReport status
        $sanctionReport = SanctionsReport::where('client_id', $client->id)->whereNull('director_id')->whereNull('contact_id')->first();

        $openpaydApis = new OpenpaydApis();
        $lorumApis = new LorumApis();
        $zenusApis = new ZenusApis();

        // $openpayd_allowed_currencies = explode(',', strtoupper($openpaydApis->getAllowedCurrencies()));
        $openpayd_allowed_currencies = $openpaydApis->getAllowedCurrencies(true) ?: [];
        $lorum_allowed_currencies = $lorumApis->getAllowedCurrencies(true) ?: [];
        $zenus_allowed_currencies = $zenusApis->getAllowedCurrencies(true) ?: [];
        $exclude_naics_code = $lorumApis->getExcludeNAICSCodes();

        $virtual_banks_allowed_currencies = array_unique(array_merge($openpayd_allowed_currencies, $lorum_allowed_currencies, $zenus_allowed_currencies));

        if ($client->parent_id) {
            session()->flash('parent_client_id', $client->parent_id);
        }
        $industries = RiskScoreMatrix::where('matrix_type', 'industries')
            ->select('id', 'title')  // Only select 'id' and 'title' columns
            ->get();


        $fingateClient = FingateClient::where('client_id', $client->id)->first();
        $card = null;
        if ($fingateClient) {
            $card = CardDetail::where('customer_id', $fingateClient->customer_id)->first();
        }

        $soc_codes = SOCCode::all();
        $naics_codes = NAICSCode::whereNotIn('naics_code', $exclude_naics_code)->get();

        $enabledModules = Common::getEnabledModules();
        $virtual_providers_map = ['OpenPayd', 'Lorum', 'Zenus'];
        $virtual_providers = array_values(array_filter($virtual_providers_map, function ($provider) use ($enabledModules) {
            return in_array($provider, $enabledModules);
        }));

        $zenus_master_bank_details = [];

        // ---------------------------
        if (in_array("Zenus", $enabledModules)) {
            $module_config = Common::getModulesConfig("Zenus");
            $zenus_master_bank_details = [
                "bank_name" => $module_config['bank_name']['value'] ?? null,
                "bank_address" => $module_config['bank_address']['value'] ?? null,
                "master_vrn" => $module_config['master_vrn']['value'] ?? null,
                "master_ach_routing_number" => $module_config['master_ach_routing_number']['value'] ?? null,
                "master_fed_wire_routing_number" => $module_config['master_fed_wire_routing_number']['value'] ?? null,
                "master_account_routing_number" => $module_config['master_account_routing_number']['value'] ?? null,
                "master_account_bic" => $module_config['master_account_bic']['value'] ?? null,
                "master_bank_intermediatry_bic" => $module_config['master_bank_intermediatry_bic']['value'] ?? null,
                "master_bank_intermediatry_bank_name" => $module_config['master_bank_intermediatry_bank_name']['value'] ?? null,
                "master_bank_intermediatry_routing_number" => $module_config['master_bank_intermediatry_routing_number']['value'] ?? null,
            ];
        }

        $sumsubClientId = '';
        $module = Module::where('name', 'Sumsub')->first();
        $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($moduleConfig) {
            $sumsubConfig = unserialize($moduleConfig->configurations);
            $sumsubClientId = $sumsubConfig['sumsub_client_id']['value'] ?? '';
        }

        $companyApplicantId = $clientKYC ? $clientKYC->company_applicant_id : null;
        $company_transaction_log = [];
        if ($companyApplicantId) {
            $company_transaction_log = DB::table('crypto_transactions_log')
                ->where('transaction_id', $companyApplicantId)
                ->where('crypto_system', 'Sumsub')
                ->orderBy('id', 'DESC')
                ->get();
        }

        // 2. Fetch ALL Individual Beneficiary Logs (Owners + Directors)
        $directorApplicantIds = ClientKYC::where('client_id', $client->id)
            ->pluck('sumsub_applicant_id')
            ->filter()
            ->toArray();

        $director_transaction_log = [];
        if (!empty($directorApplicantIds)) {
            $director_transaction_log = DB::table('crypto_transactions_log')
                ->whereIn('transaction_id', $directorApplicantIds)
                ->where('crypto_system', 'Sumsub')
                ->orderBy('id', 'DESC')
                ->get();
        }
        //dd($client->directors);
        return view("client-management.edit-company", compact(
            'currencies',
            'companyTypes',
            'fileTypes',
            'client',
            'clients',
            'clientKYC',
            'clientLinks',
            'changes',
            'isDraft',
            'nationalities',
            'passport_url',
            'onboardingFeeRecord',
            'sanctionReport',
            'clientRiskrating',
            'clientScoringData',
            'industries',
            'units',
            'virtual_banks_allowed_currencies',
            'card',
            'soc_codes',
            'naics_codes',
            'virtual_providers',
            'zenus_master_bank_details',
            'sumsubClientId',
            'company_transaction_log',
            'director_transaction_log'
        ));
        // return view("client-management.edit-company", compact('currencies', 'companyTypes', 'fileTypes', 'client', 'clients', 'clientKYC', 'clientLinks', 'changes', 'isDraft', 'nationalities', 'passport_url', 'onboardingFeeRecord', 'sanctionReport', 'card'));
    }

    // public function viewCompany($client_id, $nid="")
    // {
    //     $currencies = Currency::where("is_active", true)->get();
    //     $companyTypes = CompanyType::get();
    //     $fileTypes = ClientFileConfig::company()->get();
    //     $client = Client::find ($client_id);
    //     $changes = self::getChanges($client);

    //     if ($client->status == "Closed") {
    //         return view("/client-management/clients/".$client->type."/view/".$client->id, compact('currencies', 'companyTypes', 'fileTypes', 'client'));
    //     }

    //     if ($nid > 0){
    //         Common::readNotification($nid);
    //     }

    //     // check if client is draft
    //     $isDraft = ClientHelper::isDraftClient($client->id);

    //     return view("client-management.edit-company", compact('currencies', 'companyTypes', 'fileTypes', 'client', 'changes', 'isDraft'));
    // }

    public function updateCompany(Request $request)
    {
        $this->validate($request, [
            'name' => ['required', new NotNullAndNotEmptyRule],
            'registration_date' => 'required',
            'expiry_date' => 'required',
            'company_place' => ['required', new NotNullAndNotEmptyRule],
            'company_type' => 'required',
            'business_reg_number' => ['required', new NotNullAndNotEmptyRule],
            'nature_of_business' => ['required', new NotNullAndNotEmptyRule],
            'business_reg_address' => ['required', new NotNullAndNotEmptyRule],
            'introducer' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->get('sub_accounts_prefix')) {
            $this->validate($request, [
                'sub_accounts_prefix' => ['alpha', 'min:3', 'max:5']
            ]);
        }

        try {
            $belongsTOClient = IDORHelper::isLinkedOrSubAccount(Auth::user(), $request->get("client_id"));

            if ($belongsTOClient) {
                // create client
                $client = Client::find($request->get("client_id"));

                // validate individual update
                $response = false; // self::validateClientName($request, $client);

                if ($response) {
                    return redirect(url()->previous())->with('error', $response['error']);
                }

                if ($client) {
                    if ($request->has('unit_id')) {
                        $client->unit_id = $request->get('unit_id');
                    }

                    $client->name = $request->get('name');
                    $client->send_welcome_email = ($request->get("send_welcome_email")) ? 1 : 0;
                    $client->send_ssi = ($request->get("send_ssi")) ? 1 : 0;
                    $client->create_user = ($request->get("create_user") || $client->parent_id) ? 1 : 0;
                    $client->charge_maintenance_fee = ($request->get("charge_maintenance_fee")) ? 1 : 0;

                    if (Auth::user()->hasRole("super admin")) {
                        $client->is_b2b_internal_transfer_allowed = ($request->get("is_b2b_internal_transfer_allowed")) ? 1 : 0;
                    }

                    $client->updated_by = Auth::user()->id;
                    $client->sub_accounts_prefix = $request->get('sub_accounts_prefix');

                    if (empty($client->introducer)) {
                        $client->introducer = $request->get('introducer');
                    }

                    $client->save();

                    // udpdate company profile
                    $company = $client->company;

                    $company->registration_date = Carbon::createFromFormat('d/m/Y', $request->get('registration_date'))->format('Y-m-d');
                    $company->expiry_date = Carbon::createFromFormat('d/m/Y', $request->get('expiry_date'))->format('Y-m-d');
                    $company->company_place = $request->get("company_place");
                    $company->company_type = $request->get("company_type");
                    $company->business_reg_number = $request->get("business_reg_number");
                    $company->business_reg_address = $request->get("business_reg_address");
                    $company->nature_of_business = $request->get("nature_of_business");
                    $company->agree_declaration = ($request->get("agree_declaration")) ? 1 : 0;
                    $company->company_country = $request->get("company_country");
                    $company->naics_code = $request->get("naics_code");
                    $company->tax_number = $request->get("tax_number");
                    $company->updated_by = Auth::user()->id;

                    if (Common::isModuleEnabled('Zenus') && !Auth::user()->hasRole("draft client")) {
                        $is_previous_address_diff = ($company->business_reg_street_address  !== $request->get("business_reg_street_address"))
                            || ($company->business_street_2_address !== $request->get("business_street_2_address"))
                            || ($company->company_region !== $request->get("company_region"))
                            || ($company->company_city !== $request->get("company_city"))
                            || ($company->zip_code !== $request->get("zip_code"));

                        $company->business_reg_street_address = $request->get("business_reg_street_address");
                        $company->business_street_2_address = $request->get("business_street_2_address");
                        $company->company_region = $request->get("company_region");
                        $company->company_city = $request->get("company_city");
                        $company->zip_code = $request->get("zip_code");

                        $company->save();

                        $virtual_client = $client->virtualBankClient?->where('provider_name', 'zenus')
                            ->where('is_uob_client', false)
                            ->first();

                        // Need to Update Person Address in the Zenus System.
                        if ($is_previous_address_diff && $virtual_client) {
                            $zenus = new ZenusController();
                            $zenus->updatePersonAddress($client, $virtual_client);
                        }
                    } else {
                        $company->business_reg_street_address = $request->get("business_reg_street_address");
                        $company->business_street_2_address = $request->get("business_street_2_address");
                        $company->company_region = $request->get("company_region");
                        $company->company_city = $request->get("company_city");
                        $company->zip_code = $request->get("zip_code");

                        $company->save();
                    }

                    // update account title
                    self::updateAccountTitle($client);
                }
            }
        } catch (Exception $e) {
            $message = $e->getMessage();

            if (strpos($message, "Duplicate")) {
                $message = "Already exists!";
            }
            //  else {
            //     $message = "Internal Server Error, please try again later or contact the Tech team.";
            // }
            return redirect(url()->previous())->with('error', $message);
        }

        if (!Common::canUserMakeChange()) {
            return redirect(url()->previous())->with('success', "Changes has been submitted to your line manager for approval.");
        } else {
            return redirect(url()->previous())->with('success', "Company details updated successfully")->with("formnumber", 1);
        }
    }

    public function updateCompanyContact(Request $request)
    {
        $this->validate($request, [
            'name' => ['required', new NotNullAndNotEmptyRule],
            'country_code_id' => 'required|max:4',
            'mobile_number' => ['required', new NotNullAndNotEmptyRule],
            //'dob' => ['required', new NotNullAndNotEmptyRule],
            //'gender' => ['required', new NotNullAndNotEmptyRule],
            //'passport_number' => ['required', new NotNullAndNotEmptyRule],
            //'passport_expiry_date' => ['required', new NotNullAndNotEmptyRule],
            //'nationality_id' => ['required', new NotNullAndNotEmptyRule]
        ]);

        $clientPOC = ClientCompanyContact::where('client_id', $request->get("client_id"))->first();

        if (empty($clientPOC) || !empty($clientPOC) && $clientPOC->email_address != $request->get("email_address")) {
            $this->validate($request, [
                'email_address' => [
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email',
                    'unique:client_company_contact_person,email_address',
                    'unique:client_individuals,email_address',
                    'unique:client_directors,email'
                ]
            ]);

            if (!empty($clientPOC)) {
                self::updateUserAccountEmail($request->get("email_address"), $clientPOC->email_address, $request->get("client_id"));
            }
        }

        try {
            $belongsTOClient = IDORHelper::isLinkedOrSubAccount(Auth::user(), $request->get("client_id"));
            $errors = [];
            if ($belongsTOClient) {
                $client = Client::find($request->get("client_id"));

                // if ($client->parent_id) {
                //     $linkedUserId = ClientUser::select('user_id')->where('client_id', $client->parent_id)->first()->user_id;
                //     ClientHelper::linkUserToClient($client, $client->parent_id, $linkedUserId);
                // }
                if (Auth::user()->hasRole("client") || Auth::user()->hasRole("draft client")) {
                    $clientCompany = ClientCompany::where("client_id", $request->get("client_id"))->first();
                    if ($clientCompany->id != $request->get("company_id")) {
                        return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
                    }
                }

                if ($client->company->contact) {

                    $contact = $client->company->contact;
                    $contact->updated_by = Auth::user()->id;
                } else {
                    $contact = new ClientCompanyContact();
                    $contact->created_by = Auth::user()->id;
                }

                $contact->client_id = $request->get("client_id");
                $contact->company_id = $request->get("company_id");
                $contact->name = $request->get("name");



                if (!Auth::user()->hasRole("draft client")) {

                    if ($contact->email_address != $request->get("email_address")) {
                        $previous_email = $contact->email_address ?? "";
                        $new_email = $request->get('email_address');
                        if ($previous_email !== $new_email) {
                            self::updateUserAccountEmail($new_email, $previous_email, $request->get("client_id"));
                        }
                    }

                    $contact->email_address = $request->get("email_address");
                    $new_country_code_id = $request->get('country_code_id');
                    $new_mobile_number = $request->get('mobile_number');

                    if (($new_country_code_id != $contact->country_code_id) || ($new_mobile_number != $contact->mobile_number)) {
                        $phone_code = Country::find($new_country_code_id)->phonecode ?? '';

                        $response = self::updateMobileNumberInMatchmove(
                            $client->id,
                            $new_mobile_number,
                            $phone_code,
                        );

                        if (!empty($response) && $response->IsSucceeded === false) {
                            return redirect(url()->previous())->with('error', 'Matchmove Error: ' . $response->ErrorDescription ?? "Failed to update mobile number in Matchmove, please try again later or contact Tech team.");
                        }
                    }
                }

                $contact->country_code_id = $request->get('country_code_id');
                $contact->mobile_number = $request->get('mobile_number');
                $contact->designation = $request->get("designation");
                $contact->soc_code = $request->get("soc_code");


                $gender =  $request->get("gender") ?? null;

                if (!empty($request->get("passport_expiry_date")))
                    $passport_expiry = $request->get("passport_expiry_date");
                else
                    $passport_expiry =  $request->get('passport_expiry') ?? null;



                if ($passport_expiry)
                    $passport_expiry = Carbon::createFromFormat('d/m/Y', $passport_expiry)->format('Y-m-d');

                $dob =  $request->get('dob') ?? null;
                if ($dob)
                    $dob = Carbon::createFromFormat('d/m/Y', $dob)->format('Y-m-d');


                $contact->passport_number = $request->get("passport_number");
                $contact->passport_expiry = $passport_expiry;
                $contact->dob = $dob;
                $contact->gender = strtolower($gender);
                $contact->nationality_id = $request->get("nationality_id");
                $contact->residence_country_id = $request->get("residence_country_id");

                if (Common::isModuleEnabled('Zenus') && !Auth::user()->hasRole("draft client")) {
                    $is_previous_address_diff = ($contact->street_address  !== $request->get("street_address"))
                        || ($contact->street_2_address !== $request->get("street_2_address"))
                        || ($contact->state_region !== $request->get("state_region"))
                        || ($contact->company_city !== $request->get("company_city"))
                        || ($contact->zip_code !== $request->get("zip_code"));

                    $contact->address = $request->get("address");
                    $contact->street_address = $request->get("street_address");
                    $contact->street_2_address = $request->get("street_2_address");
                    $contact->contact_city = $request->get("contact_city");
                    $contact->state_region = $request->get("state_region");
                    $contact->zip_code = $request->get("zip_code");

                    $contact->save();

                    $virtual_client = $client->virtualBankClient?->where('provider_name', 'zenus')
                        ->where('is_uob_client', true)
                        ->first();

                    // Need to Update Person Address in the Zenus System.
                    if ($is_previous_address_diff && $virtual_client) {
                        $zenus = new ZenusController();
                        $zenus->updatePersonAddress($client, $virtual_client);
                    }
                } else {
                    $contact->address = $request->get("address");
                    $contact->street_address = $request->get("street_address");
                    $contact->street_2_address = $request->get("street_2_address");
                    $contact->contact_city = $request->get("contact_city");
                    $contact->state_region = $request->get("state_region");
                    $contact->zip_code = $request->get("zip_code");

                    $contact->save();
                }

                $directors_errors = $this->saveEntity($request, $client, 'directors');
                $signatories_errors = $this->saveEntity($request, $client, 'signatories');
                //dd($errors);
                //dd($signatories);
                $client->company->contact = $contact;
                $parent_client_id = $request->get('parent_client_id') ? $request->get('parent_client_id') : $client->parent_id;

                if ($parent_client_id && $client->company->contact->email_address) {
                    session()->flash('parent_client_id', $parent_client_id);
                    Log::info('Method: ' . __METHOD__ . ' | Parent ID: ' . $parent_client_id);
                    $linkedUserId = ClientUser::select('user_id')->where('client_id', $parent_client_id)->first()->user_id;

                    ClientHelper::linkUserToClient($client, $parent_client_id, $linkedUserId);
                }
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), "Duplicate")) {
                $message = "Already exists!";
            } else {
                $message = "Internal Server Error, please try again later or contact the Tech team." . $e->getMessage();
            }

            Log::error('Exception Message: ' . $e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());

            return redirect(url()->previous())->with('error', $message)->with("formnumber", 2);
        }
        $errors = array_merge($errors, $directors_errors, $signatories_errors);
        // dd($errors);
        return redirect(url()->previous())->with('success', "Company contact updated successfully")->with("formnumber", 2)->withErrors($errors);
    }

    public function requestSumsubKyc(Request $request)
    {
        $request->validate([
            'applicant_id' => 'required|string',
            'level_name' => 'required|string|in:liveness-link,passport-link,passport-n-liveness-link,individual-questionnaire-only,company-questionnaire-only',
            'client_id' => 'required',
            'director_id' => 'nullable',
            'reason' => 'nullable|string'
        ]);

        $client = Client::find($request->client_id);

        if (!$client) {
            return response()->json(['status' => 'error', 'message' => 'Client not found']);
        }

        DB::beginTransaction();

        if (!empty($client->parent_id) && ($request->level_name === 'company-questionnaire-only' || $request->level_name === 'individual-questionnaire-only')) {
            $client->status = 'Rejected';
            $client->reject_reason = $request->reason;
            $client->rejected_by = Auth::user()->id;
            $client->save();
        }

        $userId = $client->id;

        if ($client->type === 'company') {
            if ($request->level_name !== 'company-questionnaire-only') {
                $clientKYC = ClientKYC::where('sumsub_applicant_id', $request->applicant_id)->first();

                if ($clientKYC && !empty($clientKYC->sumsub_beneficiary_id)) {
                    $userId = $clientKYC->sumsub_beneficiary_id;
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Beneficiary ID missing for this applicant. Please ensure the main company applicant has been processed first.']);
                }
            }
        }
        $module = Module::where('name', 'Sumsub')->first();
        $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$moduleConfig) {
            return response()->json(['status' => 'error', 'message' => 'Sumsub configuration missing']);
        }

        $sumsubConfig = unserialize($moduleConfig->configurations);
        $envPrefix = app()->environment('production') ? 'production_' : 'sandbox_';
        $appToken  = $sumsubConfig[$envPrefix . 'app_token']['value'] ?? '';
        $secretKey = $sumsubConfig[$envPrefix . 'secret_key']['value'] ?? '';
        $ttlInSecs = $sumsubConfig[$envPrefix . 'time_to_leave']['value'] ?? 86400;
        $baseUrl = rtrim($sumsubConfig['base_url']['value'] ?? 'https://api.sumsub.com', '/');

        // GET CURRENT APPLICANT LEVEL FROM LOGS
        $currentLevelName = null;

        $latestLog = DB::table('crypto_transactions_log')
            ->where('transaction_id', $request->applicant_id)
            ->orderBy('id', 'desc')
            ->first();

        if ($latestLog && !empty($latestLog->details)) {
            $logDetails = json_decode($latestLog->details, true);
            $currentLevelName = $logDetails['levelName'] ?? ($logDetails['review']['levelName'] ?? null);
        }

        Log::info("Applicant ID: {$request->applicant_id} | Current Level (from logs): {$currentLevelName} | Requested Level: {$request->level_name}");

        // CALL SUMSUB API TO CHANGE LEVEL & RESET STEPS
        $changeLevelPath = '/resources/applicants/' . $request->applicant_id . '/moveToLevel?name=' . $request->level_name;
        $changeLevelTs = time();
        $changeLevelBody = '';

        $docSets = [];

        if (in_array($request->level_name, ['individual-questionnaire-only', 'company-questionnaire-only'])) {
            $docSets[] = ['idDocSetType' => 'QUESTIONNAIRE'];
        } elseif ($currentLevelName === $request->level_name) {
            switch ($request->level_name) {
                case 'passport-link':
                    $docSets[] = ['idDocSetType' => 'IDENTITY'];
                    break;
                case 'liveness-link':
                    $docSets[] = ['idDocSetType' => 'SELFIE'];
                    break;
                case 'passport-n-liveness-link':
                    $docSets[] = ['idDocSetType' => 'IDENTITY'];
                    $docSets[] = ['idDocSetType' => 'SELFIE'];
                    break;
            }
        }

        if (!empty($docSets)) {
            $changeLevelBody = json_encode(['docSets' => $docSets]);
            Log::info("Sending docSets to reset: " . $changeLevelBody);
        } else {
            Log::info("Different levels or no docSets required for moveToLevel.");
        }

        $changeLevelSignature = hash_hmac('sha256', $changeLevelTs . 'POST' . $changeLevelPath . $changeLevelBody, $secretKey);

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-App-Token' => $appToken,
                'X-App-Access-Sig' => $changeLevelSignature,
                'X-App-Access-Ts' => $changeLevelTs,
            ];

            if (!empty($changeLevelBody)) {
                $changeLevelResponse = Http::withHeaders($headers)
                    ->send('POST', $baseUrl . $changeLevelPath, [
                        'body' => $changeLevelBody
                    ]);
            } else {
                $changeLevelResponse = Http::withHeaders($headers)
                    ->send('POST', $baseUrl . $changeLevelPath);
            }

            if (!$changeLevelResponse->successful()) {
                Log::warning("Sumsub moveToLevel returned an error. Response: " . $changeLevelResponse->body());
                return response()->json(['status' => 'error', 'message' => 'Failed to change applicant level.']);
            } else {
                Log::info("Successfully processed moveToLevel for applicant {$request->applicant_id}");
            }
        } catch (Exception $e) {
            Log::error("Exception changing Sumsub level: " . $e->getMessage());
        }

        // GENERATE NEW WEBSDK LINK
        $urlPath = '/resources/sdkIntegrations/levels/-/websdkLink';
        $method = 'POST';
        $ts = time();
        $bodyParams = [
            'ttlInSecs' => $ttlInSecs,
            'userId' => (string) $userId,
            'levelName' => $request->level_name
        ];

        $bodyJson = json_encode($bodyParams);
        $signature = hash_hmac('sha256', $ts . $method . $urlPath . $bodyJson, $secretKey);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-App-Token' => $appToken,
                'X-App-Access-Sig' => $signature,
                'X-App-Access-Ts' => $ts,
            ])->post($baseUrl . $urlPath, $bodyParams);

            if ($response->successful()) {
                $responseData = $response->json();
                $generatedUrl = $responseData['url'] ?? null;

                if (!$generatedUrl) {
                    return response()->json(['status' => 'error', 'message' => 'Sumsub did not return a valid URL.']);
                }
                Log::info("Generated custom KYC link for Applicant {$request->applicant_id} (Sumsub UserId: {$userId}): {$generatedUrl} | Level: {$request->level_name}");

                $director_id = $request->director_id;
                $director = ClientDirector::find($director_id);
                if (!empty($director)) {
                    $director->kyc_status = 1;
                    $director->save();
                } else {
                    $client->is_kyc_requested = 1;
                    $client->save();
                }

                // Map levelName
                $kyc_req = new KycRequest();

                $kyc_req->created_by = Auth::id();
                $kyc_req->expiry = date("Y-m-d H:i:s", strtotime("+1 week"));
                $kyc_req->hash = $generatedUrl;
                $kyc_req->client_id = $client->id;
                $kyc_req->request_questionnaire = in_array($request->level_name, ['individual-questionnaire-only', 'company-questionnaire-only']) ? 1 : 0;
                $kyc_req->reset_reason = $request->reason ?? null;
                if (!empty($director_id)) {
                    $kyc_req->director_id = $director_id;
                }
                $kyc_req->request_passport = 0;
                $kyc_req->request_selfie = 0;
                $kyc_req->request_signature = 0;
                $kyc_req->save();

                DB::commit();

                EmailNotification::notifySumsubKYCRequest($client, $generatedUrl, $request->reason, $director_id);
                // $emailToLookup = $client->type == 'company' ? ($client->company->contact->email_address ?? '') : ($client->individual->email_address ?? '');

                // $user = User::where("email", $emailToLookup)->first();
                // if ($user && !$user->hasRole("API") && empty($director_id)) {

                //     $user->removeRole("client");

                //     $user->assignRole("draft client");

                // }
                return response()->json([
                    'status' => 'success',
                    'message' => 'Sumsub Link generated successfully.',
                    'url' => $generatedUrl
                ]);
            }

            Log::error("Failed to generate Sumsub link. Status: " . $response->status() . " Body: " . $response->body());
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to generate Sumsub link.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Exception generating custom KYC link: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'API Error: ' . $e->getMessage()]);
        }
    }
    public function saveEntity($request, $client, $entityType)
    {

        $requestData = $request->input($entityType, []);
        $errors = [];
        // $requestData = $request->input('directors', []);
        foreach ($requestData as $index => $NewData) {
            $expiry = $NewData['passport_expiry'] ?? null;
            $NewData['gender'] = $NewData['gender'] ?? null;
            if ($expiry)
                $expiry = Carbon::createFromFormat('d/m/Y', $expiry)->format('Y-m-d');
            $dob = $NewData['dob'] ?? null;
            if ($dob)
                $dob = Carbon::createFromFormat('d/m/Y', $dob)->format('Y-m-d');
            $user = null;
            if (empty($NewData['id'])) {
                // Check if a director with the same email already exists and was marked as deleted
                $existingEntity = ClientDirector::where('email', $NewData['email'])
                    ->where('client_id', $request->get("client_id"))
                    ->first();
                // dd($existingEntity);
                if ($existingEntity && $existingEntity->is_deleted) {
                    Log::info("\n \n ===============  Reactivate deleted entity  =============== \n \n");
                    // Reactivate the deleted director
                    $existingEntity->update([
                        'is_deleted' => 0,
                        'name' => $NewData['name'] ?? $existingEntity->name,
                        'updated_by' => Auth::id(), // Assuming you want to log the updater's ID
                        'updated_at' => now(),
                    ]);
                    Log::info("\n \n ===============  Reactivate deleted entity  =============== \n \n");
                } elseif ($existingEntity) {
                    $errors[] =  $existingEntity->type . ' with email ' . $NewData['email'] . ' already exist.';
                } else {
                    // Create a new director record
                    if (empty($NewData['name']) || empty($NewData['email'])) {
                        //return false;
                        continue;
                    } else {
                        $is_email_exists = self::checkEmailExists($request, $entityType, $index);
                        if ($is_email_exists) {
                            $errors[] = $NewData['email'] . ' is already taken.';
                            break;
                        }
                        // dd($NewData);
                        Log::info('\n \n ===============  Create new entity  =============== \n \n');

                        if (!empty($NewData["create-user"]) && !empty($NewData['role'])) {
                            $user = $this->createUserAndLinkToClient($NewData, $client);
                        }
                        // dd($user);
                        // Log::info($user->id);
                        // Log::info("In Create: ". json_encode($NewData));
                        $dir = ClientDirector::create([
                            'name' => $NewData['name'] ?? null,
                            'email' => $NewData['email'] ?? null,
                            'passport_number' => $NewData['passport_number'] ?? null,
                            'passport_expiry' => $expiry,
                            'dob' => $dob,
                            'user_id' => $user->id ?? null,
                            'type' => $entityType === 'directors' ? 'director' : 'signatory',
                            'gender' => strtolower($NewData['gender'] ?? ''),
                            'nationality_id' => $NewData['nationality_id'] ?? null,
                            'residence_country_id' => $NewData['residence_country_id'] ?? null,
                            'client_id' => $client->id,
                            'created_by' => Auth::id(),
                        ]);
                        $user = null;
                        Log::info("\n \n In " . $entityType . " Created: " . json_encode($dir));
                        // dd($dir);
                    }
                }
            } elseif (!empty($NewData['id']) && !empty($NewData['name']) && !empty($NewData['email'])) {
                $existingEntityData = ClientDirector::where('id', $NewData['id'])
                    ->where('client_id', $request->get("client_id"))
                    ->first();
                // dd($existingEntityData, $NewData);
                // Update only if data is different
                if ($existingEntityData) {
                    $updateData = [];
                    if ($existingEntityData->name !== $NewData['name']) {
                        $updateData['name'] = $NewData['name'];
                    }

                    if ($existingEntityData->email !== $NewData['email']) {
                        $is_email_exists = self::checkEmailExists($request, $entityType, $index);
                        // dd($is_email_exists);
                        if ($is_email_exists) {
                            $errors[] = $NewData['email'] . ' is already taken.';
                            break;
                        }

                        $updateData['email'] = $NewData['email'];
                        self::updateUserAccountEmail($NewData['email'], $existingEntityData->email);
                    }

                    if ($existingEntityData->is_deleted !== $NewData['delete'] && $NewData['delete'] == 1) {
                        $updateData['is_deleted'] = 1;
                        $updateData['user_id'] = null;
                    }
                    if ($existingEntityData->passport_expiry !== $expiry) {

                        $updateData['passport_expiry'] =  $expiry;
                    }
                    if ($existingEntityData->passport_number !== $NewData['passport_number']) {
                        $updateData['passport_number'] = $NewData['passport_number'];
                    }

                    if ($existingEntityData->dob !== $dob) {
                        $updateData['dob'] = $dob;
                    }
                    if ($existingEntityData->gender !== $NewData['gender']) {
                        $updateData['gender'] = $NewData['gender'];
                    }
                    if ($existingEntityData->nationality_id !== $NewData['nationality_id']) {
                        $updateData['nationality_id'] = $NewData['nationality_id'];
                    }
                    if ($existingEntityData->nationality_id !== $NewData['residence_country_id']) {
                        $updateData['residence_country_id'] = $NewData['residence_country_id'];
                    }
                    if (!empty($existingEntityData->user_id) && !empty($NewData["role"]) && !empty($NewData["create-user"])) {
                        $user = User::where('id', $existingEntityData->user_id)->first();
                        $currentRole = $user->roles->first()->name ?? null;

                        if ($request->user()->hasRole('super admin') && $NewData['role'] !== $currentRole) {
                            // dd($user->hasRole('client'), $NewData['role'], $currentRole);

                            Log::info('\n \n ===============  Changing existing user access  =============== \n \n');
                            Log::info('Role: ' . $NewData['role'] . ',  Email' . $NewData['email']);

                            if (strtolower($NewData['role']) === 'full access' && $currentRole === 'basic client') {
                                $user->removeRole($currentRole);
                                $user->assignRole('client');
                            } else if (strtolower($NewData['role']) === 'view access' && $currentRole === 'client') {
                                $user->removeRole($currentRole);
                                $user->assignRole('basic client');
                            }
                        }
                    }

                    if (empty($existingEntityData->user_id) && !empty($NewData["create-user"]) && !empty($NewData["role"])) {
                        // dd($existingEntityData, $NewData);
                        // dd($updateData);

                        Log::info('\n \n ===============  Create User for existing entity  =============== \n \n');
                        Log::info('Role: ' . $NewData['role'] . ',  Email ' . $NewData['email']);

                        $user = $this->createUserAndLinkToClient($NewData, $client);
                        // dd($user);
                        $updateData['user_id'] = $user->id;
                    }

                    if (!empty($updateData)) {
                        $updateData['updated_by'] = Auth::id(); // Assuming you want to log the updater's ID
                        $updateData['updated_at'] = now(); // Update the timestamp

                        ClientDirector::where('id', $NewData['id'])
                            ->where('client_id', $request->get("client_id"))
                            ->update($updateData);


                        // Delete the user if the entity is deleted
                        if (!empty($updateData['is_deleted'])) {

                            Log::info('\n \n ===============  deleting user, for existing entity  =============== \n \n');

                            $client_user_link = ClientUser::where('client_id', $existingEntityData->client_id)->where('user_id', $existingEntityData->user_id)->first();
                            if ($client_user_link) {
                                $client_user_link->delete();
                            }
                            $user = User::where('id', $existingEntityData->user_id)->first();
                            if ($user) {
                                $user->delete();
                            }
                        }
                    }
                }
            }
        }

        return $errors; // Assuming you want to return a success status
    }

    protected function checkEmailExists($request, $entityType, $index)
    {
        $check_email = Validator::make($request->all(), [
            $entityType . '.' . $index . '.email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
                'unique:client_company_contact_person,email_address',
                'unique:client_individuals,email_address',
                'unique:client_directors,email',
            ],
        ]);
        return $check_email->fails();
    }

    public function createUserAndLinkToClient($NewData, $client)
    {
        // create user linked to client.
        $user = User::where('email', $NewData['email'])->first();
        if (empty($user) && !empty($NewData['email']) && !empty($NewData['name']) && !empty($NewData["create-user"]) && !empty($NewData["role"])) {

            Log::info('\n \n ===============  Create User for new entity  =============== \n \n');

            $user = new User();
            $user->name = $NewData['name'];
            $user->email = $NewData['email'];
            $user->client_id = $client->id;
            $user->email_verified_at = Carbon::now()->format('Y-m-d H:i:s');
            // create random password
            $password = str::random(8) . "@" . rand(100, 500);
            $user->password = bcrypt($password);
            $user->save();

            if (strtolower($NewData['role']) == 'full access') {
                $user->assignRole('client');
            } else if (strtolower($NewData['role']) == 'view access') {
                $user->assignRole('basic client');
            }

            Log::info('\n \n ===============  Linking user to the client  =============== \n \n');

            // link the signatory to the main user.
            Common::addUserToClient($client, $user);
            Log::info($user);

            // send email to user
            // EmailNotification::notifyUserCredentials($user, $password);
        }
        return $user;
    }

    public function updateCompanyCurrency(Request $request)
    {
        $client = Client::find($request->get("client_id"));

        if ($client) {
            if ($request->get("currency") || $request->get('openpayd_currency')) {

                if ($request->get("currency") && count($request->get("currency")) > 0) {
                    foreach ($request->get("currency") as $currency) {

                        $exists = $client->company->accounts
                            //->whereNull('openpayd_account_id')
                            ->where('account_currency', $currency)
                            ->first();
                        //dd($exists);
                        if (!$exists) {
                            $account = $this->createCurrencyAccount($client, $currency, $request, 0);
                        } else {
                            return redirect("/client-management/clients/company/edit/" . $client->id)->with('warning', "Account already exists for the selected currency");
                        }
                    }
                }

                // if ($request->get('openpayd_currency') && count($request->get("openpayd_currency")) > 0) {

                //     if (!Common::isModuleEnabled('OpenPayd')) {
                //         return redirect(url()->previous())->with('error', 'OpenPayd Module is disabled')->with('formnumber', 3);
                //     }

                //     foreach ($request->get("openpayd_currency") as $currency) {
                //         $currency_code = Common::getCurrencyShort($currency);
                //         $exists = OpenPaydBankAccount::where('client_id', $client->id)->where('currency', explode(' ', $currency_code)[0])->first();
                //         $iban_currencies_ids = $request->get('openpayd_currency');

                //         if (empty($client->company->company_city) || empty($client->company->company_country)) {
                //             return redirect(url()->previous())->with('error', 'Please fill in both the City and Country fields for the Company.')->with('formnumber', 3);
                //         }

                //         if (!$exists) {
                //             $account = $this->createCurrencyAccount($client, $currency, $request, 1);
                //         } else {
                //             return redirect()->back()->with('error', "$currency_code currency account already exits.");
                //         }

                //         if (in_array($account->currency->id, $iban_currencies_ids) && $this->is_openpayd_enabled) {

                //             $openpaydApis = new OpenpaydApis();
                //             $openpayd_client = $openpaydApis->storeOpenPaydClient($client);
                //             if ($openpayd_client && empty($openpayd_client->errorCode)) {
                //                 $bank_account = $openpaydApis->storeOpenPaydAccount($account, $openpayd_client);
                //                 // dd($bank_account);
                //                 if (!empty($bank_account->errorCode)) {
                //                     $account->delete();
                //                     return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
                //                 }

                //                 $account->account_number = $bank_account->account_number;
                //                 $account->openpayd_account_id = $bank_account->id;
                //                 $account->save();
                //             } else {
                //                 $account->delete();
                //                 return redirect(url()->previous())->with('error', $openpayd_client->errorCode);
                //             }
                //         }
                //     }
                // }

                if (Auth::user()->hasRole("operations"))
                    return redirect("/client-management/clients/company/edit/" . $client->id)->with('success', "Account added successfully, your manager will review and approve accordingly.");
                else
                    return redirect("/client-management/clients/company/edit/" . $client->id)->with('success', "Account added successfully");
            } else {
                return redirect("/client-management/clients/company/edit/" . $client->id)->with('error', "Please select currency");
            }
        } else {
            return redirect("/client-management/clients/")->with('error', "The client doesn't exists");
        }
    }

    public function createCurrencyAccount($client, $currency, $request, $is_openpayd = 0)
    {
        $account = new Account();

        if ($is_openpayd === 1) {
            $account_number = 'OPD' . Common::getNewClientAccountNumber($client->type, ($request->get("is_crypto") ? true : false));
            $account_title = $client->name . " (" . Common::getCurrencyShort($currency) . ") - SEPA";
        } else {
            $account_number = Common::getNewClientAccountNumber($client->type, ($request->get("is_crypto") ? true : false));
            $account_title = $client->name . " (" . Common::getCurrencyShort($currency) . ")";
        }

        $account->client_id = $request->get("client_id");
        if ($client->type == "company") {
            $account->company_id = $client->company->id;
        } else {
            $account->individual_id = $client->individual->id;
        }
        $account->type = $client->is_bank ? 'bank' : $client->type;
        $account->title = $account_title;
        $account->account_number = $account_number;
        $account->account_currency = $currency;
        $account->is_active = (Auth::user()->manager_id > 0) ? 0 : 1;
        $account->is_crypto = Common::isCryptoCurrency($currency);
        $account->created_by = Auth::user()->id;

        $account->save();

        $parent_client_id = $request->get('parent_client_id') ? $request->get('parent_client_id') : $client->parent_id;

        if ($parent_client_id) {
            session()->flash('parent_client_id', $parent_client_id);
            ClientHelper::prefexAccountNumbersOfSubAccount([$account], $parent_client_id);
        }
        return $account;
    }

    public function deleteCompanyAccount(Request $request)
    {
        $account = Account::find($request->get("account_id"));

        if ($account->transactions()->count()) {
            return redirect("/client-management/clients/company/edit/" . $request->get("client_id"))->with('error', "The account can't be deleted due to present transactions, need to delete transactions first");
        } else {
            MobileRequest::where('client_id', $account->client_id)
                ->where('currency_id', $account->account_currency)
                ->delete();

            $account->delete();
        }

        return redirect("/client-management/clients/company/edit/" . $request->get("client_id"))->with('success', "Client account is deleted successfully");
    }

    public function deleteDraftAccount(Request $request)
    {
        $draft_account = ClientDraftCurrency::find($request->get("draft_account_id"));

        if ($draft_account) {
            $draft_account->delete();
        }

        return redirect(url()->previous())->with('success', "Client account is deleted successfully");
    }

    public function approveCompanyAccount($id)
    {
        $account = Account::find($id);

        if ($account->client->status == "Rejected") {
            return redirect(url()->previous())->with('error', "You cannot approve the account as the client is rejected.");
        }

        if (Auth::user()->can("approve account")) {
            $account->is_active = true;
            $account->save();

            return redirect(url()->previous())->with('success', "Account Approved successfully.");
        } else {
            return redirect(url()->previous())->with('error', "You don't have permission to approve client account.");
        }
    }

    public function rejectCompanyAccount($id)
    {
        $account = Account::find($id);

        if (Auth::user()->can("reject account")) {
            $account->is_active = false;
            $account->save();

            return redirect(url()->previous())->with('success', "Account Rejected successfully.");
        } else {
            return redirect(url()->previous())->with('error', "You don't have permission to reject client account.");
        }
    }

    public function deleteCompanyFile(Request $request)
    {
        try {
            $belongsTOClient = IDORHelper::isLinkedOrSubAccount(Auth::user(), $request->get("client_id"));
            if ($belongsTOClient) {
                $clientCompanyFile = ClientCompanyFile::where("client_id", $request->get("client_id"))->where("id", $request->get("file_id"))->first();
                if (Auth::user()->hasRole("basic client") || Auth::user()->hasRole("draft client")) {
                    if ($clientCompanyFile->id != $request->get("file_id")) {
                        return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
                    }
                }


                // soft delete file record
                $clientCompanyFile->is_deleted = true;
                $clientCompanyFile->save();


                $file = ClientCompanyFile::find($request->get("file_id"));

                // delete physical file
                // S3Helper::fileDelete($file->url);

                //delete file record from kyc for passport
                $client = Client::find($request->get("client_id"));

                $passport_id = ClientFileConfig::where('type', $client->type)->where('identifier', 'passport_copy')->value('id');

                // $passport = ClientCompanyFile::where('id', $request->get("file_id"))
                //     ->where('client_id', $client->id)
                //     ->where('company_id', $client->company->id)
                //     ->where('file_id', $passport_id)
                //     ->first();

                if ($file && $passport_id == $request->get("file_id")) {
                    $kyc = ClientKYC::where('client_id', $client->id)
                        ->where('passport_copy_url', $file->url)
                        ->first() ?? new ClientKYC();
                    $kyc->client_id = $client->id;
                    $kyc->passport_copy_url = "";
                    $kyc->selfie_photo_url = $kyc->selfie_photo_url ? $kyc->selfie_photo_url : "";
                    $kyc->kyc_status = 'Unverified';
                    $kyc->save();
                }

                // delete file record
                // $file->delete();

                // // delete file record from client company files
                // $passport->delete();

                return redirect(url()->previous())->with('success', "Client files has been deleted successfully")->with("formnumber", 3);
            }
        } catch (Exception $e) {
            $message = "Internal Server Error, please try again later or contact the Tech team.";
            return redirect(url()->previous())->with('error', $message);
        }
    }

    public function updateCompanyFiles(Request $request)
    {
        try {
            $belongsTOClient = IDORHelper::isLinkedOrSubAccount(Auth::user(), $request->get("client_id"));

            //is_passport_changed if change we need to hit the compare faces api
            $is_passport_changed = false;

            if ($belongsTOClient) {
                $client = Client::find($request->get("client_id"));
                // dd($client->directors);
                $config = $client->is_bank == 1 ? ClientFileConfig::bank()->get() : ClientFileConfig::company()->get();

                if ($request->hasFile('client-passport')) {
                    $files = $request->file('client-passport');
                    $conf = $config->where('identifier', 'passport_copy')->first();
                    $director_id = $request->get("passport_for_client_id");

                    foreach ($files as $file) {
                        $this->validate($request, [
                            'client-passport' => [new SupportingDocumentsRule(true)]
                        ]);
                        // dd($request->get('passport-file-name'));
                        // validate file
                        if (!FileFirewall::isAllowedType($file)) {
                            return FileFirewall::logBadFileException($file);
                        }

                        // upload file
                        $url = S3Helper::fileUpload($file, "client-onboarding-files/clients/company/" . $request->get('client_id'));

                        $kyc = !empty($director_id) ? ClientKYC::where('client_id', $client->id)->where('director_id', $director_id)->first() : ClientKYC::where('client_id', $client->id)->where('director_id', null)->first();

                        if ($kyc) {
                            $kyc->passport_copy_url = $url;
                            $kyc->save();
                        } else {
                            $kyc = new ClientKYC();
                            $kyc->client_id = $client->id;
                            $kyc->director_id = $director_id;
                            $kyc->passport_copy_url = $url;
                            $kyc->kyc_status = 'Unverified';
                            $kyc->save();
                        }

                        // dd($kyc);
                        // $is_passport_changed = ClientHelper::addPassportUrlToClientKYC($url, Client::find($request->get("client_id")), $conf);

                        if ($url) {
                            $new_file = new ClientCompanyFile();
                            $new_file->client_id = $request->get("client_id");
                            $new_file->company_id = $request->get("company_id");
                            $new_file->file_id = $conf->id;
                            $new_file->url = $url;
                            $new_file->file_name = $request->get("passport-file-name");
                            if ($director_id !== null) {
                                $new_file->director_id = $director_id;
                            }
                            $new_file->created_by = Auth::user()->id;
                            $new_file->save();
                            // dd($new_file);
                        } else {
                            return redirect(url()->previous())->with('error', "There was an error while uploading (" . $conf->title . ")")->with("formnumber", 3);
                        }
                    }
                }

                foreach ($config as $conf) {

                    // check if request has the file defined in config
                    if (array_key_exists('file-' . $conf->id, $request->all())) {

                        if (Auth::user()->hasRole("client") || Auth::user()->hasRole("draft client")) {
                            $clientCompany = ClientCompany::where("client_id", $request->get("client_id"))->first();
                            if ($clientCompany->id != $request->get("company_id")) {
                                return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
                            }
                        }

                        // check if the config file is single or multiple
                        if (!$conf->isMultiple) {
                            $this->validate($request, [
                                'file-' . $conf->id => [new SupportingDocumentsRule(true)]
                            ]);
                            // validate file
                            if (!FileFirewall::isAllowedType($request->file("file-" . $conf->id))) {
                                return FileFirewall::logBadFileException($request->file("file-" . $conf->id));
                            }

                            $url = S3Helper::fileUpload($request->file("file-" . $conf->id), "client-onboarding-files/clients/company/" . $request->get('client_id'));

                            if ($url) {
                                $new_file = new ClientCompanyFile();
                                $new_file->client_id = $request->get("client_id");
                                $new_file->company_id = $request->get("company_id");
                                $new_file->file_id = $conf->id;
                                $new_file->url = $url;
                                $new_file->created_by = Auth::user()->id;
                                $new_file->save();
                            } else {
                                return redirect(url()->previous())->with('error', "There was an error while uploading (" . $conf->title . ")")->with("formnumber", 4);
                            }
                        } else {

                            foreach ($request->file('file-' . $conf->id) as $file) {
                                $this->validate($request, [
                                    'file-' . $conf->id => [new SupportingDocumentsRule(true)]
                                ]);

                                // validate file
                                if (!FileFirewall::isAllowedType($file)) {
                                    return FileFirewall::logBadFileException($file);
                                }

                                // upload file
                                $url = S3Helper::fileUpload($file, "client-onboarding-files/clients/company/" . $request->get('client_id'));

                                $is_passport_changed = ClientHelper::addPassportUrlToClientKYC($url, Client::find($request->get("client_id")), $conf);

                                if ($url) {

                                    $new_file = new ClientCompanyFile();

                                    $new_file->client_id = $request->get("client_id");
                                    $new_file->company_id = $request->get("company_id");
                                    $new_file->file_id = $conf->id;
                                    $new_file->url = $url;
                                    $new_file->created_by = Auth::user()->id;
                                    $new_file->save();
                                } else {
                                    return redirect(url()->previous())->with('error', "There was an error while uploading (" . $conf->title . ")")->with("formnumber", 3);
                                }
                            } // end foreach multi file upload
                        } // end else for multi file if condition
                    } // end for checking if file exists
                } // end foreach loop over file config

                //verify client auto calling compare faces api
                $client = Client::find($request->input('client_id'));
                $kyc = ClientKYC::where('client_id', $client->id)->first();

                if ($is_passport_changed && $kyc && $kyc->kyc_status == 'Unverified' && $kyc->passport_copy_url != "") {

                    ClientHelper::compareClientPhotos($kyc->selfie_photo_url, $kyc->passport_copy_url, $client);
                }
                return redirect(url()->previous())->with('success', "Client files has been uploaded successfully")->with("formnumber", 4);
            }
        } catch (Exception $e) {
            $message = "Internal Server Error, please try again later or contact the Tech team.";

            if ($e->getCode() == 413) {
                $message = $e->getMessage();
            }

            return redirect(url()->previous())->with('error', $message);
        }
    }

    public function updateIndividualFiles(Request $request)
    {
        try {
            $belongsTOClient = IDORHelper::isLinkedOrSubAccount(Auth::user(), $request->get("client_id"));

            //is_passport_changed if change we need to hit the compare faces api
            $is_passport_changed = false;

            if ($belongsTOClient) {

                $config = ClientFileConfig::individual()->get();

                foreach ($config as $conf) {

                    // check if request has the file defined in config
                    if (array_key_exists('file-' . $conf->id, $request->all())) {

                        if (Auth::user()->hasRole("client") || Auth::user()->hasRole("draft client")) {
                            $clientIndividual = ClientIndividual::where("client_id", $request->get("client_id"))->first();
                            if ($clientIndividual->id != $request->get("individual_id")) {
                                return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
                            }
                        }

                        // check if the config file is single or multiple
                        if (!$conf->isMultiple) {

                            $this->validate($request, [
                                'file-' . $conf->id => [new SupportingDocumentsRule(true)]
                            ]);
                            // validate file
                            if (!FileFirewall::isAllowedType($request->file("file-" . $conf->id))) {
                                return FileFirewall::logBadFileException($request->file("file-" . $conf->id));
                            }

                            $url = S3Helper::fileUpload($request->file("file-" . $conf->id), "client-onboarding-files/clients/individual/" . $request->get('client_id'));

                            $is_passport_changed = ClientHelper::addPassportUrlToClientKYC($url, Client::find($request->get("client_id")), $conf);

                            if ($url) {

                                $new_file = new ClientIndividualFile();

                                $new_file->client_id = $request->get("client_id");
                                $new_file->individual_id = $request->get("individual_id");
                                $new_file->file_id = $conf->id;
                                $new_file->url = $url;
                                $new_file->created_by = Auth::user()->id;
                                $new_file->save();
                            } else {
                                return redirect(url()->previous())->with('error', "There was an error while uploading (" . $conf->title . ")");
                            }
                        } else {

                            foreach ($request->file('file-' . $conf->id) as $file) {
                                $this->validate($request, [
                                    'file-' . $conf->id => [new SupportingDocumentsRule(true)]
                                ]);
                                // validate file
                                if (!FileFirewall::isAllowedType($file)) {
                                    return FileFirewall::logBadFileException($file);
                                }

                                // upload file
                                $url = S3Helper::fileUpload($file, "client-onboarding-files/clients/individual/" . $request->get('client_id'));

                                if ($url) {

                                    $new_file = new ClientIndividualFile();

                                    $new_file->client_id = $request->get("client_id");
                                    $new_file->individual_id = $request->get("individual_id");
                                    $new_file->file_id = $conf->id;
                                    $new_file->url = $url;
                                    $new_file->created_by = Auth::user()->id;
                                    $new_file->save();
                                } else {
                                    return redirect(url()->previous())->with('error', "There was an error while uploading (" . $conf->title . ")");
                                }
                            } // end foreach multi file upload
                        } // end else for multi file if condition
                    } // end for checking if file exists
                } // end foreach loop over file config

                //        // generate and send PDF for client signature
                //        $client = ClientHelper::getClientByUserId(Auth::user()->id);
                //        EmailNotification::sendPDF($client);

                //verify client auto calling compare faces api
                $client = Client::find($request->input('client_id'));
                $kyc = ClientKYC::where('client_id', $client->id)->first();

                if ($is_passport_changed && $kyc && $kyc->kyc_status == 'Unverified' && $kyc->passport_copy_url != "") {

                    ClientHelper::compareClientPhotos($kyc->selfie_photo_url, $kyc->passport_copy_url, $client);
                }
                return redirect(url()->previous())->with('success', "Client files has been uploaded successfully")->with("formnumber", 4);
            }
        } catch (Exception $e) {
            $message = "Internal Server Error, please try again later or contact the Tech team.";

            if ($e->getCode() == 413) {
                $message = $e->getMessage();
            }

            return redirect(url()->previous())->with('error', $message);
        }
    }

    public function storeCompanyFiles(Request $request)
    {

        try {
            $belongsTOClient = IDORHelper::isLinkedOrSubAccount(Auth::user(), $request->get("client_id"));

            if ($belongsTOClient) {
                $client = Client::find($request->get("client_id"));
                $config = $client->is_bank == 1 ? ClientFileConfig::bank()->get() : ClientFileConfig::company()->get();
                $data = [];

                foreach ($config as $conf) {
                    if (array_key_exists('file-' . $conf->id, $request->all())) {
                        if (!$conf->isMultiple) {

                            $this->validate($request, [
                                'file-' . $conf->id => [new SupportingDocumentsRule(true)]
                            ]);
                            // validate file
                            if (!FileFirewall::isAllowedType($request->file("file-" . $conf->id))) {
                                return FileFirewall::logBadFileException($request->file("file-" . $conf->id));
                            }
                            $data[] = [
                                'name'     => 'file-' . $conf->id,
                                'contents' => fopen($request->file("file-" . $conf->id), 'r'),
                                'filename' => $request->file("file-" . $conf->id)->getClientOriginalName(),
                            ];
                        } else {
                            foreach ($request->file('file-' . $conf->id) as $file) {
                                $this->validate($request, [
                                    'file-' . $conf->id => [new SupportingDocumentsRule(true)]
                                ]);
                                // validate file
                                if (!FileFirewall::isAllowedType($file)) {
                                    return FileFirewall::logBadFileException($file);
                                }
                                $data[] = [
                                    'name'     => 'file-' . $conf->id . '[]',
                                    'contents' => fopen($file, 'r'),
                                    'filename' => $file->getClientOriginalName(),
                                ];
                            }
                        }
                    }
                }
                $data = ['multipart' => $data];

                //generating token to access the registration call
                $token = auth()->user()->createToken(env('TOKEN_NAME'))->plainTextToken;


                //fetching data from onboadring module
                $onboardingHelper = new OnboardingHelper();
                $baseUrl = $onboardingHelper->getBaseUrlOnboarding();
                $apiKey = $onboardingHelper->getApiKey();
                $uploadFiles = $onboardingHelper->getUploadFilesEndpoint();

                $clientRequest = new GuzzleHttpClient();

                $clientRequest->post($baseUrl . $uploadFiles, [
                    'headers'   => [
                        'api-key'        => $apiKey,
                        'accept'         => 'application/json',
                        'Authorization' => 'Bearer ' . $token,
                    ],
                    'multipart' => $data['multipart'],
                ]);
            }
        } catch (Exception $e) {
            $message = "Internal Server Error, please try again later or contact the Tech team.";

            if ($e->getCode() == 413) {
                $message = $e->getMessage();
            }

            return redirect(url()->previous())->with('error', $message . $e->getMessage());
        }
        return redirect("/dashboard/draft-client")->with("success", "Information submitted successfully");
    }

    public function addIndividual($parent_client_id = null)
    {
        $nationalities = Country::get();

        return view("client-management.new-individual", compact('nationalities', 'parent_client_id'));
    }

    public function storeIndividual(Request $request)
    {
        $this->validate(
            $request,
            [
                'name' => ['required', new NotNullAndNotEmptyRule],
                'dob' => 'required',
                'nationality_id' => 'required|integer',
                'residence_country_id' => 'required|integer',
                'country_code_id' => 'required|integer',
                'mobile_number' => 'required|max:15',
                'email_address' => [
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email',
                    'unique:client_company_contact_person,email_address',
                    'unique:client_individuals,email_address',
                    'unique:client_directors,email'
                ],
                'passport_number' => ['required', new NotNullAndNotEmptyRule, 'unique:client_individuals,passport_number'],
                'passport_expiry_date' => 'required',
                'mailing_address' => ['required', new NotNullAndNotEmptyRule],
                'introducer' => ['nullable', 'string', 'max:255'],
            ],
            [
                'nationality_id.required' => 'The Nationality field is required.',
                'residence_country_id.required' => 'The Country of residence field is required.'
            ]
        );

        if ($request->get('sub_accounts_prefix')) {
            $this->validate($request, [
                'sub_accounts_prefix' => ['alpha', 'min:3', 'max:5', 'unique:clients,sub_accounts_prefix']
            ]);
        }

        try {
            // create client
            $client = new Client();

            $client->name = $request->get('name');
            //$client->unit_id = $request->get('unit_id');
            $client->type = "individual";
            $client->status = (Auth::user()->manager_id > 0) ? "Draft" : "Pending";
            $client->send_welcome_email = ($request->get("send_welcome_email")) ? 1 : 0;
            $client->send_ssi = ($request->get("send_ssi")) ? 1 : 0;
            $client->create_user = ($request->get("create_user") || $request->get("parent_client_id")) ? 1 : 0;

            if (Auth::user()->hasRole("super admin")) {
                $client->is_b2b_internal_transfer_allowed = ($request->get("is_b2b_internal_transfer_allowed")) ? 1 : 0;
            }

            $client->created_by = Auth::user()->id;
            $client->sub_accounts_prefix = $request->get('sub_accounts_prefix');
            $client->introducer = $request->get('introducer');
            //added for basic admin
            if (empty($client->owner_user_id) || empty($client->unit_id)  && Auth::check()) {
                if (Auth::user()->hasRole("basic admin")) {
                    $client->owner_user_id = Auth::user()->id;
                    if (!empty(Auth::user()->unit->id))
                        $client->unit_id = Auth::user()->unit->id;
                }
            }
            $client->save();

            // create individual
            $individual = new ClientIndividual();
            $individual->client_id = $client->id;
            $individual->dob = Carbon::createFromFormat('d/m/Y', $request->get('dob'))->format('Y-m-d');
            $individual->nationality_id = $request->get('nationality_id');
            $individual->residence_country_id = $request->get('residence_country_id');
            $individual->country_code_id = $request->get('country_code_id');
            $individual->mailing_address = $request->get('mailing_address');
            $individual->gender = $request->get('gender');
            $individual->mobile_number = $request->get('mobile_number');
            $individual->email_address = $request->get('email_address');
            $individual->passport_number = $request->get('passport_number');
            $individual->passport_expiry_date = Carbon::createFromFormat('d/m/Y', $request->get('passport_expiry_date'))->format('Y-m-d');
            $individual->agree_declaration = ($request->get("agree_declaration")) ? 1 : 0;
            $individual->city = $request->get('city');
            $individual->created_by = Auth::user()->id;

            $individual->save();

            if ($request->get('parent_client_id')) {
                $client->parent_id = $request->get('parent_client_id');
                $client->save();

                $linkedUserId = ClientUser::select('user_id')->where('client_id', $request->get("parent_client_id"))->first()->user_id;

                ClientHelper::linkUserToClient($client, $request->get('parent_client_id'), $linkedUserId);
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), "Duplicate")) {
                $message = "Already exists!";
            } else {
                // dd($e);
                $message = "Internal Server Error, please try again later or contact the Tech team.";
            }
            return redirect(url()->previous())->with('error', $message);
        }

        return redirect("/client-management/clients/individual/edit/" . $client->id)->with('success', "Individual added successfully");
    }

    public function submitForApproval(Request $request)
    {
        $client = Client::find($request->get("client_id"));

        $client->status = "Pending";
        $client->updated_by = Auth::user()->id;

        $client->save();

        // notify client creator manager
        EmailNotification::notifyClientCreation($client);

        return redirect("/client-management/clients/" . $client->type . "/edit/" . $client->id)->with('success', "Client submitted for approval successfully");
    }

    public function editIndividual($client_id, $nid = "")
    {
        $client = Client::find($client_id);
        $units = Unit::all();
        $clientKYC = ClientKYC::where('client_id', $client_id)->first();
        $clientLinks = [];
        $aml = new AmlRuleController();
        $aml_results = $aml->applyRules($client->individual->toArray(), 'Person');

        // $risk_data = array('total_risk_score' => 24);
        // $aml_risk_results = $aml->applyRules($risk_data, 'Risk Score');

        //dd($aml_risk_results);

        //dd($aml_results);
        $clients = Client::where('clients.id', '!=', $client_id)
            ->leftJoin('clients as parent', 'clients.parent_id', '=', 'parent.id')
            ->where(function ($query) {
                $query->where('parent.id', 0)
                    ->orWhereNull('parent.id');
            })
            ->select('clients.*')
            ->where('clients.status', 'Approved')
            ->get();

        if ($client->users->count() > 0) {
            // $clientLinksRaw = clientUser::where('user_id', $client->users[0]->user->id)->where('client_id', '!=', $client_id);
            // $clients->whereNotIn('id', $clientLinksRaw->select('client_id')->get());
            $clientLinks = $client->children;
        }

        // $clients = $clients->get();

        $currencies = Currency::where("is_active", true);

        // If client has a parent then only list those currencies in which the parent client has accounts.
        if ($client->parent_id) {
            $parentClient = Client::find($client->parent_id);
            $currencies->whereIn('id', $parentClient->clientAccounts->pluck('account_currency')->toArray())
                ->where('is_crypto', 0);
        }

        $currencies = $currencies->get();

        $nationalities = Country::get();
        $fileTypes = ClientFileConfig::Individual()->get();
        $changes = self::getChanges($client);
        $clientRiskrating = ClientRiskRating::where('client_id', $client->id)
            ->where('status', 'active')
            ->first();
        $clientScoringData = $clientRiskrating ? json_decode($clientRiskrating->scoring_data, true) : [];

        if ($client->status == "Closed") {
            return view("/client-management/view-closed-" . $client->type, compact('currencies', 'fileTypes', 'client'));
        }

        if ($nid > 0) {
            Common::readNotification($nid);
        }

        // check if client is draft
        $isDraft = ClientHelper::isDraftClient($client->id);
        $passport_url = "";

        if ($clientKYC && $clientKYC->passport_copy_url) {
            $passport_url = $clientKYC->passport_copy_url;
        } else {
            $passport_id = ClientFileConfig::where('type', $client->type)->where('identifier', 'passport_copy')->value('id');

            $passport = ClientIndividualFile::where('client_id', $client->id)
                ->where('individual_id', $client->individual->id)
                ->where('file_id', $passport_id)
                ->first();

            if ($passport && $passport->url) {
                $passport_url = $passport->url;
            } else {
                $passport_url = '';
            }
        }

        $openpaydApis = new OpenpaydApis();
        $lorumApis = new LorumApis();
        $zenusApis = new ZenusApis();

        $openpayd_allowed_currencies = $openpaydApis->getAllowedCurrencies(true) ?: [];
        $lorum_allowed_currencies = $lorumApis->getAllowedCurrencies(true) ?: [];
        $zenus_allowed_currencies = $zenusApis->getAllowedCurrencies(true) ?: [];

        $virtual_banks_allowed_currencies = array_unique(array_merge($openpayd_allowed_currencies, $lorum_allowed_currencies, $zenus_allowed_currencies));

        $onboardingFeeRecord = OnboardingFeeRecords::where("client_id", $client->id)->first();

        //get the sanctionsReport status
        $sanctionReport = SanctionsReport::where('client_id', $client->id)->first();


        $fingateClient = FingateClient::where('client_id', $client->id)->first();
        $card = null;
        if ($fingateClient) {
            $card = CardDetail::where('customer_id', $fingateClient->customer_id)->first();
        }

        $soc_codes = SocCode::all();

        $enabledModules = Common::getEnabledModules();
        $virtual_providers_map = ['OpenPayd', 'Lorum', 'Zenus'];
        $virtual_providers = array_values(array_filter($virtual_providers_map, function ($provider) use ($enabledModules) {
            return in_array($provider, $enabledModules);
        }));

        $zenus_master_bank_details = [];

        if (in_array("Zenus", $enabledModules)) {
            $module_config = Common::getModulesConfig("Zenus");
            $zenus_master_bank_details = [
                "bank_name" => $module_config['bank_name']['value'] ?? null,
                "bank_address" => $module_config['bank_address']['value'] ?? null,
                "master_vrn" => $module_config['master_vrn']['value'] ?? null,
                "master_ach_routing_number" => $module_config['master_ach_routing_number']['value'] ?? null,
                "master_fed_wire_routing_number" => $module_config['master_fed_wire_routing_number']['value'] ?? null,
                "master_account_routing_number" => $module_config['master_account_routing_number']['value'] ?? null,
                "master_account_bic" => $module_config['master_account_bic']['value'] ?? null,
                "master_bank_intermediatry_bic" => $module_config['master_bank_intermediatry_bic']['value'] ?? null,
                "master_bank_intermediatry_bank_name" => $module_config['master_bank_intermediatry_bank_name']['value'] ?? null,
                "master_bank_intermediatry_routing_number" => $module_config['master_bank_intermediatry_routing_number']['value'] ?? null,
            ];
        }

        $sumsubClientId = '';
        $module = Module::where('name', 'Sumsub')->first();
        $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($moduleConfig) {
            $sumsubConfig = unserialize($moduleConfig->configurations);
            $sumsubClientId = $sumsubConfig['sumsub_client_id']['value'] ?? '';
        }

        $transaction_log = [];
        if ($clientKYC && !empty($clientKYC->sumsub_applicant_id)) {
            $transaction_log = DB::table('crypto_transactions_log')
                ->where('transaction_id', $clientKYC->sumsub_applicant_id)
                ->where('crypto_system', 'Sumsub')
                ->orderBy('id', 'DESC')
                ->get();
        }
        //dd($card);
        return view("client-management.edit-individual", compact(
            'currencies',
            'nationalities',
            'fileTypes',
            'client',
            'clients',
            'clientKYC',
            'clientLinks',
            'changes',
            'isDraft',
            'passport_url',
            'onboardingFeeRecord',
            'sanctionReport',
            'aml_results',
            'clientRiskrating',
            'clientScoringData',
            'units',
            // 'lorum_allowed_currencies',
            // 'openpayd_allowed_currencies',
            'virtual_banks_allowed_currencies',
            'card',
            'soc_codes',
            'virtual_providers',
            'zenus_master_bank_details',
            'sumsubClientId',
            'transaction_log'
        ));
        // return view("client-management.edit-individual", compact('currencies', 'nationalities', 'fileTypes', 'client', 'clients', 'clientKYC', 'clientLinks', 'changes', 'isDraft', 'passport_url', 'onboardingFeeRecord', 'sanctionReport', 'aml_results', 'card'));
    }

    public function linkClient(Request $request)
    {
        $this->validate($request, [
            'client_id' => 'required',
            'new_client_id' => 'required'
        ]);

        $subAccount = Client::find($request->get("new_client_id"));

        if ($subAccount->status !== "Approved") {
            return redirect(url()->previous())->with('error', 'Something went wrong, please try again later.');
        }
        $linkedUserId = ClientUser::select('user_id')->where('client_id', $request->get("client_id"))->first()->user_id;

        $response = ClientHelper::linkUserToClient($subAccount, $request->get("client_id"), $linkedUserId);

        if ($response['type'] == 'success') {
            $subAccount->parent_id = $request->get("client_id");

            $subAccount->save();
        }

        if ($accounts = $subAccount->clientAccounts) {
            ClientHelper::prefexAccountNumbersOfSubAccount($accounts, $request->get("client_id"));
        }

        return redirect(url()->previous())->with($response["type"], $response["message"]);
    }

    public function unlinkClient(Request $request)
    {
        $this->validate($request, [
            'client_id' => 'required',
            'linked_client_id' => 'required'
        ]);

        $subAccount = Client::find($request->get("linked_client_id"));
        $subAccount->parent_id = 0;
        $subAccount->save();

        if ($accounts = $subAccount->clientAccounts) {
            ClientHelper::deletePrefexFromAccNumsOfSubAccount($accounts);
        }

        // Unlock the Sub-Account's own user.
        // $subAccountUser = User::where("email", $subAccount->type == 'company' ? $subAccount->company->contact->email_address : $subAccount->individual->email_address)->first();

        // if ($subAccountUser && $subAccountUser->locked) {
        //     $subAccountUser->locked = false;
        //     $subAccountUser->save();
        // }

        $linkedUserId = ClientUser::select('user_id')->where('client_id', $request->get("client_id"))->first()->user_id;

        // delete user link
        ClientUser::where('client_id', $request->get("linked_client_id"))->where('user_id', $linkedUserId)->first()->delete();

        return redirect(url()->previous())->with("success", "Client has been unlinked successfully");
    }

    public function updateIndividual(Request $request)
    {
        // dd($request->all());
        $this->validate(
            $request,
            [
                'name' => ['required', new NotNullAndNotEmptyRule],
                'dob' => 'required',
                'introducer' => ['nullable', 'max:255', 'string'],
                'nationality_id' => 'required',
                'residence_country_id' => 'required',
                'country_code_id' => 'required|max:4',
                'mobile_number' => 'required|max:15',
                'passport_number' => ['required', new NotNullAndNotEmptyRule],
                'passport_expiry_date' => 'required',
                'mailing_address' => ['required', new NotNullAndNotEmptyRule],
                'introducer' => ['nullable', 'string', 'max:255'],
            ],
            [
                'nationality_id.required' => 'The Nationality field is required.',
                'residence_country_id.required' => 'The Country of residence field is required.'
            ]
        );

        $individual = ClientIndividual::where("client_id", $request->get("client_id"))->first();

        if (!empty($individual) && $individual->email_address != $request->get("email_address")) {
            $this->validate($request, [
                'email_address' => [
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email',
                    'unique:client_company_contact_person,email_address',
                    'unique:client_individuals,email_address',
                    'unique:client_directors,email'
                ]
            ]);
        }

        if ($request->get('sub_accounts_prefix')) {
            $this->validate($request, [
                'sub_accounts_prefix' => ['alpha', 'min:3', 'max:5']
            ]);
        }

        try {
            $belongsTOClient = IDORHelper::isLinkedOrSubAccount(Auth::user(), $request->get("client_id"));

            if ($belongsTOClient) {
                // update client
                $client = Client::find($request->get('client_id'));

                // validate individual update
                $response = self::validateClientName($request, $client);

                if ($response) {

                    return redirect(url()->previous())->with('error', $response['error']);
                }

                if ($client) {
                    if ($request->has('unit_id')) {
                        $client->unit_id = $request->get('unit_id');
                    }
                    $client->name = $request->get('name');
                    $client->send_welcome_email = ($request->get("send_welcome_email")) ? 1 : 0;
                    $client->send_ssi = ($request->get("send_ssi")) ? 1 : 0;
                    $client->create_user = ($request->get("create_user") || $client->parent_id) ? 1 : 0;
                    $client->charge_maintenance_fee = ($request->get("charge_maintenance_fee")) ? 1 : 0;

                    if (Auth::user()->hasRole("super admin")) {
                        $client->is_b2b_internal_transfer_allowed = ($request->get("is_b2b_internal_transfer_allowed")) ? 1 : 0;
                    }

                    $client->updated_by = Auth::user()->id;
                    $client->sub_accounts_prefix = $request->get('sub_accounts_prefix');
                    //$client->introducer  = '';
                    if (empty($client->introducer)) {
                        $client->introducer = $request->get('introducer');
                    }
                    //dd($client);
                    $client->save();

                    // update individual
                    if (Auth::user()->hasRole("client") || Auth::user()->hasRole("draft client")) {

                        if ($individual->id != $request->get("individual_id")) {
                            return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
                        }
                    }

                    if ($individual) {

                        //dd( $request->all());
                        $individual->client_id = $client->id;
                        $individual->dob = Carbon::createFromFormat('d/m/Y', $request->get('dob'))->format('Y-m-d');
                        $individual->nationality_id = $request->get('nationality_id');
                        $individual->residence_country_id = $request->get('residence_country_id');
                        $individual->country_code_id = $request->get('country_code_id');
                        $individual->mailing_address = $request->get('mailing_address');
                        $individual->gender = $request->get('gender');
                        // $individual->mobile_number = $request->get('mobile_number');

                        if (!Auth::user()->hasRole("draft client")) {
                            $previous_email = $individual->email_address ?? "";
                            $new_email = $request->get('email_address');
                            if (!$client->parent_id && $previous_email !== $new_email) {
                                $response = self::updateUserAccountEmail($new_email, $previous_email, $client->id);

                                if (!empty($response) && $response->IsSucceeded === false) {
                                    return redirect(url()->previous())->with('error', 'Matchmove Error: ' . $response->ErrorDescription ?? "Failed to update email in Matchmove, please try again later or contact Tech team.");
                                }

                                $individual->email_address = $request->get('email_address');
                            }

                            $previous_mobile_number = $individual->mobile_number ?? "";
                            if ($previous_mobile_number !== $request->get('mobile_number')) {
                                // update mobile number in MatchMove
                                $phone_code = Country::find($request->get('country_code_id'))->phonecode ?? "";
                                $response = self::updateMobileNumberInMatchmove($client->id, $request->get('mobile_number'),  $phone_code);

                                if (!empty($response) && $response->IsSucceeded === false) {
                                    return redirect(url()->previous())->with('error', 'Matchmove Error: ' . $response->ErrorDescription ?? "Failed to update mobile number in Matchmove, please try again later or contact Tech team.");
                                }

                                $individual->mobile_number = $request->get('mobile_number');
                            }
                        }

                        $individual->passport_number = $request->get('passport_number');
                        $individual->passport_expiry_date = Carbon::createFromFormat('d/m/Y', $request->get('passport_expiry_date'))->format('Y-m-d');
                        $individual->agree_declaration = ($request->get("agree_declaration")) ? 1 : 0;
                        $individual->city = $request->get('city');
                        $individual->updated_by = Auth::user()->id;

                        if (Common::isModuleEnabled('Zenus') && !Auth::user()->hasRole("draft client")) {
                            $is_previous_address_diff = ($individual->street_address  !== $request->get("street_address"))
                                || ($individual->street_2_address !== $request->get("street_2_address"))
                                || ($individual->state_region !== $request->get("state_region"))
                                || ($individual->city !== $request->get("city"))
                                || ($individual->zip_code !== $request->get("zip_code"));

                            $individual->street_address = $request->get("street_address");
                            $individual->street_2_address = $request->get("street_2_address");
                            $individual->state_region = $request->get("state_region");
                            $individual->zip_code = $request->get("zip_code");
                            $individual->save();

                            $virtual_client = $client->virtualBankClient?->where('provider_name', 'zenus')->first();

                            // Need to Update Person Address in the Zenus System.
                            if ($is_previous_address_diff && $virtual_client) {
                                $zenus = new ZenusController();
                                $zenus->updatePersonAddress($client, $virtual_client);
                            }
                        } else {
                            $individual->street_address = $request->get("street_address");
                            $individual->street_2_address = $request->get("street_2_address");
                            $individual->state_region = $request->get("state_region");
                            $individual->zip_code = $request->get("zip_code");
                            $individual->save();
                        }

                        // update account title
                        self::updateAccountTitle($client);
                    } else {
                        return redirect(url()->previous())->with('error', "Individual doesn't exists")->with("formnumber", 0);
                    }
                } else {
                    return redirect(url()->previous())->with('error', "Client doesn't exists")->with("formnumber", 0);
                }
            }
        } catch (Exception $e) {
            //dd($e);
            if (strpos($e->getMessage(), "Duplicate")) {
                $message = "Already exists!";
            } else {
                $message = "Internal Server Error, please try again later or contact the Tech team.";
            }
            return redirect(url()->previous())->with('error', $message);
        }

        if (!Common::canUserMakeChange()) {
            return redirect(url()->previous())->with('success', "Changes has been submitted to your line manager for approval.");
        } else {
            return redirect(url()->previous())->with('success', "Individual client updated successfully")->with("formnumber", 1);
        }
    }

    public function uploadSelfiePhoto(Request $request)
    {
        $request->validate([
            'selfie_photo_url' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png']
        ]);

        $client = Client::find($request->get('client_id'));

        if (!$client) {
            return redirect(url()->previous())->with('error', "Client doesn't exist")->with("formnumber", 0);
        }

        if (!$request->hasFile('selfie_photo_url')) {
            return back()->with('error', "No file uploaded");
        }

        $file = $request->file('selfie_photo_url');
        if (!FileFirewall::isAllowedType($file)) {
            return FileFirewall::logBadFileException($file);
        }

        $folder = $client->type === 'individual'
            ? "client-onboarding-files/clients/individuals/"
            : "client-onboarding-files/clients/companies/";

        $url = S3Helper::fileUpload(
            $request->file('selfie_photo_url'),
            $folder . $client->id
        );

        if ($client->type === 'individual') {
            $kyc = ClientKYC::where("client_id", $client->id)->first();
        } else {
            if ($request->get('is_owner')) {
                $kyc = ClientKYC::where("client_id", $client->id)->whereNull('director_id')->first();
            } else {
                $kyc = ClientKYC::where("director_id", $request->get('director_id'))->first();
            }
        }


        if ($kyc && $kyc->selfie_photo_url) {
            S3Helper::fileDelete($kyc->selfie_photo_url);
        }

        // If no KYC record, create new one
        if (!$kyc) {
            $kyc = new ClientKYC();
            $kyc->client_id = $client->id;
            if ($client->type === 'company') {
                $kyc->director_id = $request->get('is_owner') ? null : $request->get('director_id');
            }
            $kyc->kyc_status = 'Unverified';
            $kyc->compliance_status = 'Pending';
            $kyc->liveness_status = 'Liveness Done';
        }

        // Save selfie URL
        $kyc->selfie_photo_url = $url;
        $kyc->save();

        return redirect(url()->previous())->with('success', "Selfie photo uploaded successfully")->with("formnumber", 1);
    }

    protected static function updateMobileNumberInMatchmove($client_id, $mobile_number, $phone_code)
    {
        $fingateClient = FingateClient::where('client_id', $client_id)->first();
        if ($fingateClient) {
            $matchMove = new FingateHelper();
            $payload = [
                'CustomerId' => $fingateClient->customer_id,
                'InternationalPhoneCode' => $phone_code,
                'CellPhoneNumber' => $mobile_number
            ];
            // dd($payload);
            $response = $matchMove->UpdateCustomerMobileNumber($payload);
            // dd($response);
            return $response;
        }
    }

    protected static function updateUserAccountEmail($new_email, $previous_email, $client_id = null)
    {

        // if ($client->users) {
        //     $user = ClientHelper::getUserByClientId($client->id);

        //     $user->email = $request->get("email_address");

        //     $user->save();
        // }

        // $cu = ClientUser::where("client_id", $client->id)->first();

        // if ($cu) {
        //     // $user = User::find($cu->user_id);
        //     $user = User::where('email', $request->get("email_address"))->first();

        //     if ($user) {
        //         $user->email = $request->get("email_address");
        //         $user->save();
        //     }
        // }
        $user = User::where('email', $previous_email)->first();
        if ($user) {
            $user->email = $new_email;
            // $user->save();

            if ($client_id) {
                $fingateClient = FingateClient::where('client_id', $client_id)->first();

                // if client is linked to MatchMove, update email address in MatchMove
                if (!empty($fingateClient)) {
                    $matchMove = new FingateHelper();
                    $payload = [
                        'CustomerId' => $fingateClient->customer_id,
                        'Email' => $new_email
                    ];

                    $response = $matchMove->UpdateCustomerEmail($payload);

                    if ($response->IsSucceeded) {
                        $user->save();
                        return $response;
                    } else {
                        // return redirect(url()->previous())->with('error', $response->ErrorDescription)->with("formnumber", 0);
                        return $response;
                    }
                }
            } else {
                $user->save();
            }
        }
    }

    protected static function ValidateIndividualEmail($request, $individual)
    {
        // check if the email doesn't belongs to another user
        if ($request->get("email_address") != $individual->email_address) {

            // check if the email address already exists for another user
            if (User::where("email", $request->get("email_address"))->count() > 0) {
                return [
                    'valid' => false,
                    'error' => "The email address is already taken, please choose a different email address."
                ];
            }
        }
    }

    protected static function validateClientName($request, $client)
    {
        if ($request->get("name") != $client->name) {

            // check if the name belongs to another client
            if (Client::where("name", $request->get("name"))->count() > 0) {
                return [
                    'valid' => false,
                    'error' => "The Client name is already taken, please choose a different name."
                ];
            }
        }
    }

    protected static function validateCompanyContactEmail($request, $contact)
    {
        // check if the email doesn't belongs to another user
        if ($request->get("email_address") != $contact->email_address) {

            // check if the email address already exists for another user
            if (User::where("email", $request->get("email_address"))->count() > 0) {
                return [
                    'valid' => false,
                    'error' => "The email address is already taken, please choose a different email address."
                ];
            }
        }
    }

    public function updateIndividualEmployer(Request $request)
    {
        $this->validate($request, [
            'occupation_business' => ['required', new NotNullAndNotEmptyRule],
            'employer_business_name' => ['required', new NotNullAndNotEmptyRule],
            'employer_address' => ['required', new NotNullAndNotEmptyRule],
            'job_title' => ['required', new NotNullAndNotEmptyRule],
            'applicant_annual_income_usd' => 'required|integer'
        ]);
        // Accessing the stored array from the session
        $steps = session('steps', []);

        // Merge the existing $steps array with the new fields
        $steps = array_merge($steps, $request->only([
            'occupation_business',
            'employer_business_name',
            'employer_address',
            'job_title',
            'applicant_annual_income_usd'
        ]));


        try {
            $belongsTOClient = IDORHelper::isLinkedOrSubAccount(Auth::user(), $request->get("client_id"));
            if ($belongsTOClient) {
                $employer = ClientIndividualEmployer::where("client_id", $request->get("client_id"))->first();

                if (Auth::user()->hasRole("client") || Auth::user()->hasRole("draft client")) {
                    $clientIndividual = ClientIndividual::where("client_id", $request->get("client_id"))->first();
                    if ($clientIndividual->id != $request->get("individual_id")) {
                        return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
                    }
                }

                if (!$employer) {
                    $employer = new ClientIndividualEmployer();
                    $employer->created_by = Auth::user()->id;
                } else {
                    $employer->updated_by = Auth::user()->id;
                }

                $employer->client_id = $request->get("client_id");
                $employer->individual_id = $request->get("individual_id");
                $employer->occupation_business = $request->get("occupation_business");
                $employer->employer_business_name = $request->get("employer_business_name");
                $employer->employer_address = $request->get("employer_address");
                $employer->job_title = $request->get("job_title");
                $employer->soc_code = $request->get('occupation_soc_code');
                $employer->applicant_annual_income_usd = $request->get("applicant_annual_income_usd");

                $employer->save();

                return redirect(url()->previous())->with('success', "Individual employer updated successfully")->with('formnumber', 2);
            }
        } catch (Exception $e) {
            $message = "Internal Server Error, please try again later or contact the Tech team.";
            return redirect(url()->previous())->with('error', $message);
        }
    }

    public function updateIndividualCurrency(Request $request)
    {
        $client = Client::find($request->get("client_id"));

        if ($client) {
            if ($request->get("currency") || $request->get("openpayd_currency")) {

                if ($request->get('currency') && count($request->get("currency")) > 0) {
                    foreach ($request->get("currency") as $currency) {

                        $exists = $client->individual->accounts
                            //->whereNull('openpayd_account_id')
                            ->where('account_currency', $currency)
                            ->first();
                        //dd($exists);
                        if (!$exists) {
                            $account = $this->createCurrencyAccount($client, $currency, $request, 0);
                        } else {
                            return redirect("/client-management/clients/individual/edit/" . $client->id)->with('warning', "Account already exists for the selected currency");
                        }
                    }
                }

                // if ($request->get('openpayd_currency') && count($request->get("openpayd_currency")) > 0) {

                //     if (!Common::isModuleEnabled('OpenPayd')) {
                //         return redirect(url()->previous())->with('error', 'OpenPayd Module is disabled')->with('formnumber', 3);
                //     }

                //     foreach ($request->get("openpayd_currency") as $currency) {
                //         $currency_code = Common::getCurrencyShort($currency);
                //         $exists = OpenPaydBankAccount::where('client_id', $client->id)->where('currency', explode(' ', $currency_code)[0])->first();
                //         $iban_currencies_ids = $request->get('openpayd_currency');

                //         if (empty($client->individual->residence_country_id) || empty($client->individual->city)) {
                //             return redirect(url()->previous())->with('error', 'Please fill in both the City and Country fields for the individual.')->with('formnumber', 3);
                //         }
                //         if (empty($client->individual->employer->employer_address)) {
                //             return redirect(url()->previous())->with('error', 'Please fill in the individual employer address.')->with('formnumber', 3);
                //         }

                //         if (!$exists) {
                //             $account = $this->createCurrencyAccount($client, $currency, $request, 1);
                //         } else {
                //             return redirect()->back()->with('error', "$currency_code currency account already exits.");
                //         }

                //         // dd($client->individual->residence_country_id);
                //         if (in_array($account->currency->id, $iban_currencies_ids) && $this->is_openpayd_enabled) {

                //             $openpaydApis = new OpenpaydApis();
                //             $openpayd_client = $openpaydApis->storeOpenPaydClient($client);

                //             if ($openpayd_client && empty($openpayd_client->errorCode)) {
                //                 $bank_account = $openpaydApis->storeOpenPaydAccount($account, $openpayd_client);
                //                 // dd($bank_account);
                //                 if (!empty($bank_account->errorCode)) {
                //                     $account->delete();
                //                     return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
                //                 }

                //                 $account->account_number = $bank_account->account_number;
                //                 $account->openpayd_account_id = $bank_account->id;
                //                 $account->save();
                //             } else if (!empty($openpayd_client->errorCode)) {
                //                 $account->delete();
                //                 return redirect(url()->previous())->with('error', $openpayd_client->errorCode);
                //             }
                //         }
                //     }
                // }

                if (Auth::user()->hasRole("operations"))
                    return redirect("/client-management/clients/individual/edit/" . $client->id)->with('success', "Account added successfully, your manager will review and approve accordingly.");
                else
                    return redirect("/client-management/clients/individual/edit/" . $client->id)->with('success', "Account added successfully");
            } else {
                return redirect("/client-management/clients/individual/edit/" . $client->id)->with('error', "Please select currency");
            }
        } else {
            return redirect("/client-management/clients/")->with('error', "The client doesn't exists");
        }
    }

    public function storeIndividualFiles(Request $request)
    {

        try {
            $belongsTOClient = IDORHelper::isLinkedOrSubAccount(Auth::user(), $request->get("client_id"));

            if ($belongsTOClient) {
                $config = ClientFileConfig::individual()->get();

                foreach ($config as $conf) {
                    if (array_key_exists('file-' . $conf->id, $request->all())) {
                        if (!$conf->isMultiple) {

                            $this->validate($request, [
                                'file-' . $conf->id => [new SupportingDocumentsRule(true)]
                            ]);
                            // validate file
                            if (!FileFirewall::isAllowedType($request->file("file-" . $conf->id))) {
                                return FileFirewall::logBadFileException($request->file("file-" . $conf->id));
                            }
                            $data[] = [
                                'name'     => 'file-' . $conf->id,
                                'contents' => fopen($request->file("file-" . $conf->id), 'r'),
                                'filename' => $request->file("file-" . $conf->id)->getClientOriginalName(),
                            ];
                        } else {
                            foreach ($request->file('file-' . $conf->id) as $file) {
                                $this->validate($request, [
                                    'file-' . $conf->id => [new SupportingDocumentsRule(true)]
                                ]);
                                // validate file
                                if (!FileFirewall::isAllowedType($file)) {
                                    return FileFirewall::logBadFileException($file);
                                }
                                $data[] = [
                                    'name'     => 'file-' . $conf->id . '[]',
                                    'contents' => fopen($file, 'r'),
                                    'filename' => $file->getClientOriginalName(),
                                ];
                            }
                        }
                    }
                }
                $data = ['multipart' => $data];

                //generating token to access the registration call
                $token = auth()->user()->createToken(env('TOKEN_NAME'))->plainTextToken;

                //fetching data from onboadring module
                $onboardingHelper = new OnboardingHelper();
                $baseUrl = $onboardingHelper->getBaseUrlOnboarding();
                $apiKey = $onboardingHelper->getApiKey();
                $uploadFiles = $onboardingHelper->getUploadFilesEndpoint();

                $clientRequest = new GuzzleHttpClient();

                $clientRequest->post($baseUrl . $uploadFiles, [
                    'headers'   => [
                        'api-key'        => $apiKey,
                        'accept'         => 'application/json',
                        'Authorization' => 'Bearer ' . $token,
                    ],
                    'multipart' => $data['multipart'],
                ]);
            }
        } catch (Exception $e) {
            $message = "Internal Server Error, please try again later or contact the Tech team.";

            if ($e->getCode() == 413) {
                $message = $e->getMessage();
            }

            return redirect(url()->previous())->with('error', $message);
        }
        return redirect("/dashboard/draft-client")->with("success", "Information submitted successfully");
    }

    public function deleteIndividualFile(Request $request)
    {
        try {
            $belongsTOClient = IDORHelper::isLinkedOrSubAccount(Auth::user(), $request->get("client_id"));
            if ($belongsTOClient) {
                $clientIndividualFile = ClientIndividualFile::where("client_id", $request->get("client_id"))
                    ->where("id", $request->get("file_id"))
                    ->first();

                if (Auth::user()->hasRole("basic client") || Auth::user()->hasRole("draft client")) {
                    if ($clientIndividualFile->id != $request->get("file_id")) {
                        return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
                    }
                }


                // soft delete file record
                $clientIndividualFile->is_deleted = true;
                $clientIndividualFile->save();

                // $file = ClientIndividualFile::find($request->get("file_id"));


                // delete physical file
                // S3Helper::fileDelete($file->url);

                //delete file record from kyc for passport
                $client = Client::find($request->get("client_id"));

                $passport_id = ClientFileConfig::where('type', $client->type)->where('identifier', 'passport_copy')->value('id');

                $passport = ClientIndividualFile::where('client_id', $client->id)
                    ->where('individual_id', $client->individual->id)
                    ->where('file_id', $passport_id)
                    ->first();

                if ($passport && $passport->id == $request->get("file_id")) {
                    $kyc = ClientKYC::where('client_id', $client->id)->first() ?? new ClientKYC();
                    $kyc->client_id = $client->id;
                    $kyc->passport_copy_url = "";
                    $kyc->selfie_photo_url = $kyc->selfie_photo_url ? $kyc->selfie_photo_url : "";
                    $kyc->kyc_status = 'Unverified';
                    $kyc->save();
                }

                // delete file record
                // $file->delete();

                return redirect(url()->previous())->with('success', "Client files has been deleted successfully")->with("formnumber", 3);
            }
        } catch (Exception $e) {
            $message = "Internal Server Error, please try again later or contact the Tech team.";
            return redirect(url()->previous())->with('error', $message);
        }
    }

    public function deleteIndividualAccount(Request $request)
    {
        $account = Account::find($request->get("account_id"));

        if (Auth::user()->can("delete account")) {
            if ($account->transactions()->count()) {
                return redirect("/client-management/clients/individual/edit/" . $request->get("client_id"))->with('error', "The account can't be deleted due to present transactions, need to delete transactions first");
            } else {
                MobileRequest::where('client_id', $account->client_id)
                    ->where('currency_id', $account->account_currency)
                    ->delete();

                $account->delete();
            }

            return redirect("/client-management/clients/individual/edit/" . $request->get("client_id"))->with('success', "Client account is deleted successfully");
        } else {
            return redirect("/client-management/clients/individual/edit/" . $request->get("client_id"))->with('error', "You don't have permission to delete client account.");
        }
    }

    public function approveIndividualAccount($id)
    {
        $account = Account::find($id);

        if ($account->client->status == "Rejected") {
            return redirect(url()->previous())->with('error', "You cannot approve the account as the client is rejected.");
        }

        if (Auth::user()->can("approve account")) {
            $account->is_active = true;
            $account->save();

            return redirect(url()->previous())->with('success', "Account Approved successfully.");
        } else {
            return redirect(url()->previous())->with('error', "You don't have permission to approve client account.");
        }
    }

    public function rejectIndividualAccount($id)
    {
        $account = Account::find($id);

        if (Auth::user()->can("reject account")) {
            $account->is_active = false;
            $account->save();

            return redirect(url()->previous())->with('success', "Account Rejected successfully.");
        } else {
            return redirect(url()->previous())->with('error', "You don't have permission to reject client account.");
        }
    }

    /**
     * Disabling Delete-Client option on FAIB's request
     */

    public function deleteClient(Request $request)
    {
        $client = Client::find($request->get("client_id"));

        if ($client) {

            // if ($client->type == "individual"){
            //     $response = self::deleteIndividualClient($client);
            // }else{
            //     $response = self::deleteCompanyClient($client);
            // }

            // Make the client Rejected instead of deleting
            $timestamp = Carbon::now();

            $client->status = "Rejected";
            $client->rejected_at = $timestamp;
            $client->reject_reason = "Rejected by: " . Auth::user()->name . " at " . $timestamp->format('d M, Y H:i:s'); // Rejected by Admin
            $client->rejected_by = Auth::user()->id;
            $client->save();

            // Getting clients email address.
            if ($client->type == "individual") {
                $email = $client->individual->email_address;
            } else {
                $email = $client->company->contact ? $client->company->contact->email_address : null;
            }

            // Get User by Email Address.
            if (!$email) {
                return redirect("/client-management/draft-clients")->with('warning', 'Client is rejected, But User not found!');
            }

            $user = User::where('email', $email)->first();

            if ($user) {
                $UserLink = ClientUser::where('client_id', $client->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($UserLink) {
                    $UserLink->delete();
                }

                $user->delete();
            }

            // if (isset($response['error'])){
            //     return redirect("/client-management/clients")->with('error', $response['reason']);
            // }

            return redirect("/client-management/draft-clients")->with('success', "Client has been rejected and user deleted successfully!");
        }

        return redirect("/client-management/draft-clients")->with('error', 'Client details not found');
    }

    public static function deleteIndividualClient($client)
    {
        $accounts = ($client->individual) ?  $client->individual->accounts : null;
        $employer = ($client->individual) ? $client->individual->employer : null;
        $individual = ($client->individual) ? $client->individual : null;
        $client_users = ClientUser::where("client_id", $client->id)->get();
        $files = ($client->individual) ? $client->individual->files : null;

        // delete account if no account transactions are available
        if ($accounts && $accounts->count() > 0) {
            foreach ($accounts as $account) {
                if ($account->transactions()->count() > 0) {
                    return [
                        'error' => 1,
                        'reason' => "There are transactions present in the client account (" . $account->account_number . ")"
                    ];
                } else {
                    // delete account
                    $account->delete();
                }
            }
        }

        // delete individual profile
        if ($individual) {
            $client->individual->delete();
        }

        // delete employer detials
        if ($employer) {
            $client->individual->employer->delete();
        }

        // delete user account
        if ($client_users && $client_users->count() > 0) {
            foreach ($client_users as $cu) {

                $user = User::find($cu->user_id);

                if ($user) {
                    // if there are multiple roles then we just need to remove client role
                    if (count($user->getRoleNames()) > 1 && $user->hasRole("client")) {
                        $user->removeRole("client");
                    } else {

                        // delete photo
                        if ($user && $user->profile_photo_url) {
                            S3Helper::fileDelete($user->profile_photo_url);
                        }

                        // delete user
                        $user->delete();

                        // delete link
                        $cu->delete();
                    }
                }
            }
        }

        // delete files
        if ($files && count($files) > 0) {
            foreach ($files as $file) {
                S3Helper::fileDelete($file->url);
                $file->delete();
            }
        }

        // delete client account
        $client->delete();
    }

    public static function deleteCompanyClient($client)
    {
        $accounts = (isset($client->company->accounts)) ? $client->company->accounts : null;
        $contact = (isset($client->company->contact)) ? $client->company->contact : null;
        $company = (isset($client->company)) ? $client->company : null;
        $user = $client->user;
        $client_users = ClientUser::where("client_id", $client->id)->get();
        $files = (isset($client->company->files)) ? $client->company->files : null;

        // delete account if no account transactions are available
        if ($accounts && $accounts->count() > 0) {
            foreach ($accounts as $account) {
                if ($account->transactions()->count() > 0) {
                    return [
                        'error' => 1,
                        'reason' => "There are transactions present in the client account (" . $account->account_number . ")"
                    ];
                } else {
                    // delete account
                    $account->delete();
                }
            }
        }

        // delete individual profile
        if ($company) {
            $client->company->delete();
        }

        // delete employer detials
        if ($contact) {
            $client->company->contact->delete();
        }

        // delete user account
        if ($client_users && $client_users->count() > 0) {
            foreach ($client_users as $cu) {

                $user = User::find($cu->user_id);

                if ($user) {
                    // if there are multiple roles then we just need to remove client role
                    if (count($user->getRoleNames()) > 1 && $user->hasRole("client")) {
                        $user->removeRole("client");
                    } else {

                        // delete photo
                        if ($user && $user->profile_photo_url) {
                            S3Helper::fileDelete($user->profile_photo_url);
                        }

                        // delete user
                        $user->delete();

                        // delete link
                        $cu->delete();
                    }
                }
            }
        }

        // delete files
        if ($files && count($files) > 0) {
            foreach ($files as $file) {
                S3Helper::fileDelete($file->url);
                $file->delete();
            }
        }

        // delete client account
        $client->delete();
    }

    public function makeDraftToClient($client, $user)
    {
        // assign client role
        if ($user->hasRole("draft client")) {
            $user->removeRole("draft client");
            $user->assignRole("client");
        }

        // create bank accounts
        $draftCurrencies = $client->draftCurrencies;
        $virtual_bank_errors = [];

        if ($draftCurrencies) {
            $hasAPIrole = User::whereHas('roles', function ($query) {
                $query->where('name', 'API');
            })
                ->whereHas('linkClients', function ($query) use ($client) {
                    $query->where('client_id', $client->id);
                })
                ->exists();

            foreach ($draftCurrencies as $draftCurrency) {
                $currency = $draftCurrency->currency;

                if ($client->type == "individual" && $client->individual->accounts) {
                    $exists = $client->individual->accounts()->where('account_currency', $currency->id)->first();
                } elseif ($client->type == "company" && $client->company->accounts) {
                    $exists = $client->company->accounts()->where('account_currency', $currency->id)->first();
                } else {
                    $exists = false;
                }

                if (!$exists) {

                    $account = new Account();

                    $account->client_id = $client->id;

                    if ($client->type == "individual") {
                        $account->individual_id = $client->individual->id;
                    } else {
                        $account->company_id = $client->company->id;
                    }

                    $account->type = $client->type;
                    $account->title = $client->name . " (" . Common::getCurrencyShort($currency->id) . ")";
                    $account->account_number = Common::getNewClientAccountNumber($client->type, ($currency->is_crypto ? true : false));
                    $account->account_currency = $currency->id;
                    $account->is_crypto = Common::isCryptoCurrency($currency->id);
                    $account->is_active = 1;
                    $account->created_by = Auth::user()->id;

                    $account->save();

                    if (Common::isModuleEnabled($currency->virtual_iban_provider) && $hasAPIrole) {
                        Log::info("\n\n\n");
                        Log::info('------------------------------------------------');
                        Log::info('Currency Short: ' . $currency->short . ' | Currency Virtual Provider: ' . $currency->virtual_iban_provider . ' | Provider Module Status: ' . true . ' | hasAPIrole: ' . $hasAPIrole);
                        Log::info('------------------------------------------------');

                        switch ($currency->virtual_iban_provider) {
                            case 'openpayd':
                                $result = Common::generateOpenpaydAccountIBAN($account->id);
                                break;
                            case 'lorum':
                                $result = Common::generateLorumAccountIBAN($account->id);
                                break;
                            case 'zenus':
                                $result = Common::generateZenusAccountIBAN($account->id);
                                break;
                            default:
                                $result = [
                                    'success' => false,
                                    'message' => 'Virtual IBAN provider not supported.'
                                ];
                        }

                        Log::info("\n\n\n");
                        Log::info('------------------------------------------------');
                        Log::info('Result for ' . $currency->short . ': ' . print_r($result, true));
                        Log::info('------------------------------------------------');

                        if ($result['success'] !== true) {
                            $account->is_active = 0;
                            $account->save();

                            $virtual_bank_errors[] = $result['message'];
                        } else {
                            $account->is_active = 1;
                            $account->save();
                        }
                    }
                }
            }

            // send welcome email
            if ($client->send_welcome_email) {
                EmailNotification::sendWelcomeEmail($client);
            }
        }

        // delete all client notifications
        Common::deleteAllUserNotifications($user->id);

        return $virtual_bank_errors;
    }

    public function approveClient(Request $request)
    {
        //dd($request);
        $clientKYC = ClientKYC::where('client_id', $request->get("client_id"))->where('director_id', null)->first();

        // if (in_array("Compliance", \App\Helpers\Common::getEnabledModules()) && $clientKYC && $clientKYC->compliance_status != 'Verified') {
        if ($clientKYC && $clientKYC->compliance_status != 'Verified') {
            return redirect(url()->previous())->with('error', 'This Client is not verified by the Compliance yet!');
        }

        $client = Client::find($request->get("client_id"));

        // in-case of company check if the company have contact person
        if ($client->type == "company" && !$client->company->contact) {
            // if ($client->send_welcome_email || $client->send_ssi || $client->create_user) {
            return redirect("/client-management/clients/" . $client->type . "/edit/" . $client->id)->with('error', "There is no contact person associated with the client, please create one.");
            // }
        }

        $requiredDataCheck = $this->checkRequiredData($client);

        if (!$requiredDataCheck['success']) {
            return redirect("/client-management/clients/" . $client->type . "/edit/" . $client->id)->with('error', $requiredDataCheck['message']);
        }

        // approve client account
        $client->status = "Approved";
        $client->approved_at = Carbon::now();
        $client->approved_by = Auth::user()->id;
        $client->send_welcome_email = 1;
        //dd($client);
        $client->save();

        // check if client is draft
        $user = ClientHelper::getUserByClientId($client->id);

        if (($user && ($user->hasRole("draft client") || $user->hasRole("API"))) || $client->status != "Approved") {
            $virtual_bank_errors = $this->makeDraftToClient($client, $user);
        } else {
            // create user account
            if ($client->create_user) {
                Common::createClientUser($client);
            }

            // approve / reject accounts
            $virtual_bank_errors = Common::approveRejectClientAccounts($client, 1);

            if ($client->approved_by !== null && $client->users->count() > 0) {
                Common::lockUserAccount($client->users[0]->user_id, false);
            }

            // notify client creator
            EmailNotification::notifyCreatorClientApproval($client);
        }

        if ($user && $user->hasRole("API")) {
            // create separate user account for the client on client's email address.
            if ($client->create_user) {
                Common::createClientUser($client);
            }

            Common::sendWebhookNotification($client, $user, 'client', 'Your client has been Approved');
        }

        if (($accounts = $client->clientAccounts) && $client->parent_id) {
            ClientHelper::prefexAccountNumbersOfSubAccount($accounts, $client->parent_id);
        }
        $client->save();

        Log::info("\n\n\n");
        Log::info('------------------------------------------------');
        Log::info('Virtual Bank Errors: ' . print_r($virtual_bank_errors, true));
        Log::info('------------------------------------------------');

        if (count($virtual_bank_errors) > 0) {

            return redirect("/client-management/clients/" . $client->type . "/edit/" . $client->id)
                ->with('success', 'Client approved successfully.')
                ->with('warning', $virtual_bank_errors);
        }

        return redirect("/client-management/clients/" . $client->type . "/edit/" . $client->id)->with('success', 'Client approved successfully.');
    }

    private static function checkRequiredData($client)
    {
        switch ($client->type) {
            case 'company':
                $company = $client->company;

                $requiredFields = [
                    'Business Registration Number' => $company?->business_reg_number,
                    'Tax number' => $company?->tax_number,
                    'Date of Incorporation' => $company?->registration_date,
                    'Industry sector code' => $company?->naics_code,
                    'Country of company' => $company?->companyCountry?->iso,
                    'City of company' => $company?->company_city,
                    'Business Address Line 1' => $company?->business_reg_street_address,
                    'State/Region' => $company?->company_region,
                    'ZIP Code' => $company?->zip_code,
                ];
                $identifiers = [
                    'passport_copy',
                    'bank_statement',
                    'certificate_of_business_license',
                    'certificate_of_incorporation',
                    'memorandum_of_association'
                ];

                $configs = ClientFileConfig::query()
                    ->where('type', 'company')
                    ->whereIn('identifier', $identifiers)
                    ->pluck('id', 'identifier');

                $existingFileIds = $company?->files()
                    ->whereIn('file_id', $configs->values())
                    ->pluck('file_id')
                    ->unique() ?? collect();
                break;

            case 'individual':
                $individual = $client->individual;

                $requiredFields = [
                    'nationality' => $individual?->nationality?->iso,
                    'passport_number' => $individual?->passport_number,
                    'passport_expiry_date' => $individual?->passport_expiry_date,
                    'city' => $individual?->city,
                    'street_address' => $individual?->street_address,
                    'state_region' => $individual?->state_region,
                    'zip_code' => $individual?->zip_code,
                    'employer.soc_code' => $individual?->employer?->soc_code,
                ];
                $identifiers = [
                    'passport_copy',
                    'bank_statement'
                ];

                $configs = ClientFileConfig::query()
                    ->where('type', 'individual')
                    ->whereIn('identifier', $identifiers)
                    ->pluck('id', 'identifier');

                $existingFileIds = $individual?->files()
                    ->whereIn('file_id', $configs->values())
                    ->pluck('file_id')
                    ->unique() ?? collect();
                break;
            default:
                return; // Exit the function for unknown client types
        }

        $missingFields = collect($requiredFields)
            ->filter(fn($value) => blank($value))
            ->keys()
            ->values()
            ->all();

        $missingDocuments = collect($identifiers)
            ->filter(function ($identifier) use ($configs, $existingFileIds) {
                return !$existingFileIds->contains($configs->get($identifier));
            })
            ->values();

        if (empty($missingFields) && $missingDocuments->isEmpty()) {
            return [
                'success' => true,
            ];
        }

        $message = 'Client cannot be approved yet.';

        if (!empty($missingFields)) {
            $message .= ' Missing information: ' . implode(', ', $missingFields) . ' |';
        }

        if ($missingDocuments->isNotEmpty()) {
            $message .= ' Missing documents: ' . $missingDocuments->implode(', ') . '.';
        }

        return [
            'success' => false,
            'message' => $message,
        ];
    }

    public function rejectClient(Request $request)
    {
        $client = Client::find($request->get("client_id"));

        $client->status = "Rejected";
        $client->rejected_at = Carbon::now();
        $client->rejected_by = Auth::user()->id;
        $client->reject_reason = $request->get("reject_reason");

        $client->save();

        // Deactivate bank accounts
        $clientAccounts = $client->clientAccounts;

        if ($clientAccounts) {
            foreach ($clientAccounts as $clientAccount) {
                $account = Account::find($clientAccount->id);

                $account->is_active = false;
                $account->save();
            }
        }

        // approve / reject accounts
        Common::approveRejectClientAccounts($client, 0);

        if ($client->approved_by !== null && $client->users->count() > 0 && $client->users[0]->user->linkClients->count() == 1) {
            Common::lockUserAccount($client->users[0]->user_id, true);
        }

        if ($client->users->count() > 0 && $client->users[0]->user->hasRole('API')) {
            Common::sendWebhookNotification($client, $client->users[0]->user, 'client', 'Your client has been Rejected. | Reason: ' . $client->reject_reason);
        } else {
            // notify client or creator
            if (ClientHelper::isDraftClient($client->id)) {
                EmailNotification::notifyClientRejection($client);
            } else {
                EmailNotification::notifyCreatorClientReject($client);
            }
        }

        return redirect("/client-management/clients/" . $client->type . "/edit/" . $client->id)->with('success', 'Client Rejected successfully.');
    }

    public function closeClient(Request $request)
    {
        $client = Client::find($request->get("client_id"));

        Common::closeRestoreClient($client, $request->get("close_reason"));

        if ($client->directors->count() > 0) {
            foreach ($client->directors as $director) {
                Common::closeRestoreDirector($director->email);
            }
        }

        // Lock linked users that have no other active client links
        Common::closeRestoreLinkedUsers($client,  "Closed");

        return redirect("/client-management/closed-clients")->with('success', 'Client Closed successfully.');
    }

    public function restoreClient(Request $request)
    {
        $client = Client::find($request->get("client_id"));

        $closeReason = null;

        Common::closeRestoreClient($client, $closeReason, "Approved");

        if ($client->directors->count() > 0) {
            foreach ($client->directors as $director) {
                Common::closeRestoreDirector($director->email, "Approved");
            }
        }

        Common::closeRestoreLinkedUsers($client, "Approved");

        return redirect("/client-management/clients/" . $client->type . "/edit/" . $client->id)->with('success', 'Client Restored successfully.');
    }

    private static function updateUserName($client)
    {
        if ($client->isDirty("name")) {

            $cu = ClientUser::where("client_id", $client->id)->first();

            if ($cu) {
                $user = User::find($cu->user_id);

                $user->name = $client->name;
                $user->save();
            }
        }
    }

    private static function getChanges($client)
    {
        $client_changes = ChangeLogger::get($client);
        $user_changes = ($client->user) ? ChangeLogger::get($client->user) : null;
        $profile_changes = ($client->type == "company") ? ChangeLogger::get($client->company) : ChangeLogger::get($client->individual);

        $changes = [];
        if ($client_changes) {
            $changes[] = $client_changes;
        }

        if ($profile_changes) {
            $changes[] = $profile_changes;
        }

        if ($user_changes) {
            $changes[] = $user_changes;
        }

        return $changes;
    }

    public function getModelChanges(Request $request)
    {
        $client_models = [
            'App\Models\Client',
            'App\Models\ClientIndividual',
            'App\Models\ClientCompany',
            'App\Models\User',
            'App\Models\Account',
            'App\Models\ClientKYC',
        ];

        $query = ModelChange::query()
            ->where('status', 'Pending')
            ->whereIn('model', $client_models)
            ->orderBy('created_at', 'desc');

        if (!Auth::user()->hasRole('super admin')) {
            $query->where('checker_id', Auth::user()->id);
        }

        $perPage = $request->get("per_page");

        if ($request->get('search_string')) {
            $query->where(function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->get('search_string') . '%')
                    ->orWhere('reject_reason', 'like', '%' . $request->get('search_string') . '%');
            });
        };

        if ($perPage == -1 || ($request->get('search_string') && $query->count() < 10)) {
            $perPage = $query->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }

        $changes = $query->paginate($perPage);

        return view("client-management.pending-changes", compact('changes', 'request'));
    }

    public function clientNotifications()
    {
        $clients = Client::where("status", "Approved")->get();
        return view("client-management.client-notifications", compact('clients'));
    }

    public function sendClientNotifications(Request $request)
    {
        $client = Client::find($request->get('client_id'));

        if (!$client) {
            return redirect('/client-management/client-notifications')->with('error', 'Client not found');
        }

        $clientUser = $client->users[0]->user ?? null;

        // Skip if no user exists or user has API role
        if (!$clientUser || $clientUser->hasRole('API')) {

            if (!$clientUser && $request->boolean('create_user')) {
                $client->update(['create_user' => 1]);
                Common::notifyAndCreateClientUser($client);

                return redirect('/client-management/client-notifications')->with('success', 'Client notification emails are sent successfully');
            }

            return redirect('/client-management/client-notifications')->with('error', 'Invalid client user');
        }

        // Resolve client email
        $email = match ($client->type) {
            'individual' => $client->individual->email_address ?? null,
            'company'    => $client->company->contact->email_address ?? null,
            default      => null,
        };

        if (empty($email)) {
            return redirect('/client-management/client-notifications')->with('error', 'No email found for the client');
        }

        // // send welcome email
        // if ($request->get("send_ssi")) {
        //     $client->send_ssi = true;
        //     $client->save();
        // }

        // Send welcome email
        if ($request->boolean('send_welcome_email')) {
            $client->update(['send_welcome_email' => true]);
            EmailNotification::sendWelcomeEmail($client);
        }

        // Create user account
        if ($request->boolean('create_user')) {
            $client->update(['create_user' => 1]);
            Common::notifyAndCreateClientUser($client);
        }

        return redirect('/client-management/client-notifications')->with('success', 'Client notification emails are sent successfully');
    }

    private static function updateAccountTitle($client)
    {
        if ($client->clientAccounts) {
            foreach ($client->clientAccounts as $account) {
                $account->title = $client->name . " (" . Common::getCurrencyShort($account->account_currency) . ")";
                $account->save();
            }

            $subAccounts = Client::where('parent_id', $client->id)->get();

            if ($subAccounts) {
                foreach ($subAccounts as $subAccount) {
                    if ($subAccount->clientAccounts) {
                        ClientHelper::prefexAccountNumbersOfSubAccount($subAccount->clientAccounts, $client->id);
                    }
                }
            }
        }
    }

    public function downloadFiles($id)
    {
        $client = Client::find($id);


        $allFiles = [];


        if (!File::exists(storage_path('app/DownloadedFiles'))) {
            File::makeDirectory(storage_path('app/DownloadedFiles'), 0755, false, false);
        }

        if (!File::exists(storage_path('app/DownloadedFiles/' . $client->id))) {
            File::makeDirectory(storage_path('app/DownloadedFiles/' . $client->id), 0755, false, false);
        }

        if ($client->type == "individual") {
            $allFiles = ClientIndividualFile::where('client_id', $client->id)
                ->where('individual_id', $client->individual->id)
                ->where('client_individual_files.is_deleted', false)
                ->join('client_files_config', 'client_individual_files.file_id', '=', 'client_files_config.id')
                ->select('client_individual_files.*', 'client_files_config.title')
                ->get();
        } elseif ($client->type == "company") {
            $allFiles = ClientCompanyFile::where('client_id', $client->id)
                ->where('company_id', $client->company->id)
                ->where('client_company_files.is_deleted', false)
                ->join('client_files_config', 'client_company_files.file_id', '=', 'client_files_config.id')
                ->select('client_company_files.*', 'client_files_config.title')
                ->get();
        }


        $uuid = Str::uuid()->toString();
        $path = storage_path('app/tmp/' . $uuid);
        $zipname = $path . "/Documents.zip";

        if (!file_exists(storage_path($path))) {
            mkdir($path, 0777, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipname, ZipArchive::CREATE) !== true) {
            return redirect(url()->previous())->with('error', "Unable to create ZIP archive");
        }

        foreach ($allFiles as $file) {
            $contents = file_get_contents($file['url']);
            if (!$contents) {
                return redirect(url()->previous())->with('error', "Unable to download file: {$file['title']}");
            }
            $pathInfo = pathinfo($file['url']);
            $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
            $fileName = preg_replace('/[^\p{N}\p{L}\s\.\-\(\)&]/u', '', $file['title']);
            $fileName = trim($fileName) . '__' . uniqid() . $extension;
            file_put_contents(storage_path('app/DownloadedFiles/' . $client->id . '/' . $fileName), $contents);
            $zip->addFromString($fileName, $contents);
        }

        $zip->close();

        register_shutdown_function(function () use ($path) {
            File::deleteDirectory($path);
        });

        File::deleteDirectory(storage_path('app/DownloadedFiles'));

        return response()->download($zipname);
    }

    public function verifyClientKYC(Request $request)
    {
        try {
            $clientKYC = ClientKYC::find($request->get('client_kyc_id'));

            $clientKYC->compliance_status = 'Verified';

            $clientKYC->save();

            return redirect(url()->previous())->with('success', 'Client KYC has been verified successfully!')->with('formnumber', 3);
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__, $request);

            return redirect(url()->previous())->with('error', $e);
        }
    }

    public function storeClientCompany(Request $request)
    {
        $request->validate([
            'passport_file' => [new SupportingDocumentsRule],
            'signature' => 'required_without:uploadedSignature',
            'uploadedSignature' => ['required_without:signature', new SupportingDocumentsRule]
        ]);

        //fetching data from onboadring module
        $onboardingHelper = new OnboardingHelper();
        $baseUrl = $onboardingHelper->getBaseUrlOnboarding();
        $apiKey = $onboardingHelper->getApiKey();
        $client = ClientHelper::getClientByUserId(Auth::user()->id);

        $endPointMapping = [
            'Rejected' => $onboardingHelper->getUpdateCompanyEndpoint(),
            'Draft' => $onboardingHelper->getRegisterCompanyEndpoint()
        ];


        //checking if the source of the signature is from draw or file input
        $signaturePhoto = $request->input('signature') ?? $request->file('uploadedSignature');

        if ($request->input('signature') != null) {

            $base64Data = substr($signaturePhoto, strpos($signaturePhoto, ',') + 1);

            // Decode base64 data
            $binaryData = base64_decode($base64Data);

            // Create a stream from binary data
            $signaturePhotoStream = Utils::streamFor($binaryData);
        }
        //dd( $request->all());
        $directors = $request->input('directors', '');
        $data = [
            [
                'name'     => 'passport_copy',
                'contents' => fopen($request->file('passport_file'), 'r'),
                'filename' => $request->file('passport_file')->getClientOriginalName(),
            ],
            [
                'name'     => 'signature_photo',
                'contents' => $signaturePhotoStream ?? fopen($signaturePhoto, 'r'),
                'filename' => 'signature.png',
            ],
            [
                'name'     => 'contact_person_name',
                'contents' => $request->input('contact_person_name')
            ],
            [
                'name'     => 'contact_person_designation',
                'contents' => $request->input('contact_person_designation')
            ],
            [
                'name'     => 'passport_number',
                'contents' => $request->input('passport_number')
            ],
            [
                'name'     => 'passport_expiry_date',
                'contents' => $request->input('passport_expiry_date')
            ],
            [
                'name'     => 'gender',
                'contents' => $request->input('gender')
            ],
            [
                'name'     => 'nationality_id',
                'contents' => $request->input('nationality_id')
            ],
            [
                'name'     => 'residence_country_id',
                'contents' => $request->input('residence_country_id')
            ],
            [
                'name'     => 'dob',
                'contents' => $request->input('dob')
            ],
            [
                'name'     => 'directors',
                'contents' => json_encode($directors)
            ],
            [
                'name'     => 'name',
                'contents' => $request->input('name')
            ],
            [
                'name'     => 'registration_date',
                'contents' => $request->input('registration_date')
            ],
            [
                'name'     => 'expiry_date',
                'contents' => $request->input('expiry_date')
            ],
            [
                'name'     => 'company_place',
                'contents' => $request->input('company_place')
            ],
            [
                'name'     => 'company_type',
                'contents' => $request->input('company_type')
            ],
            [
                'name'     => 'business_reg_number',
                'contents' => $request->input('business_reg_number')
            ],
            [
                'name'     => 'business_reg_address',
                'contents' => $request->input('business_reg_address')
            ],
            [
                'name'     => 'nature_of_business',
                'contents' => $request->input('nature_of_business')
            ],
            [
                'name'     => 'selfie_photo',
                'contents' => $request->input('photo-url')
            ],
            [
                'name'     => 'currency_ids',
                'contents' => implode(',', $request->input('currency'))
            ],

        ];

        //dd($data);

        try {
            ClientHelper::onboardingRegisterClient($baseUrl, $endPointMapping[$client->status], $apiKey, $data);
        } catch (Exception $e) {
            Log::error('Exception thrown: ' . $e->getMessage());
            //dd($e);
            $message = "Internal Server Error, please try again later or contact the Tech team.";
            return redirect(url()->previous())->with('error', $message)->with('formnumber', 0);
        }

        return redirect(url()->previous())->with('success', 'Information submitted successfully')->with('formnumber', 5);
    }

    public function storeClientIndividual(Request $request)
    {
        $request->validate([
            'passport_file' => [new SupportingDocumentsRule],
            'signature' => 'required_without:uploadedSignature',
            'uploadedSignature' => ['required_without:signature', new SupportingDocumentsRule]
        ]);
        //fetching data from onboadring module
        $onboardingHelper = new OnboardingHelper();
        $baseUrl = $onboardingHelper->getBaseUrlOnboarding();
        $apiKey = $onboardingHelper->getApiKey();

        $client = ClientHelper::getClientByUserId(Auth::user()->id);

        $endPointMapping = [
            'Rejected' => $onboardingHelper->getUpdateIndividualEndpoint(),
            'Draft' => $onboardingHelper->getRegisterIndividualEndpoint()
        ];

        $signaturePhoto = $request->input('signature') ?? $request->file('uploadedSignature');

        if ($request->input('signature') != null) {

            $base64Data = substr($signaturePhoto, strpos($signaturePhoto, ',') + 1);

            // Decode base64 data
            $binaryData = base64_decode($base64Data);

            // Create a stream from binary data
            $signaturePhotoStream = Utils::streamFor($binaryData);
        }

        $data = [
            [
                'name'     => 'date_of_birth',
                'contents' => $request->input('dob')
            ],
            [
                'name'     => 'nationality_id',
                'contents' => $request->input('nationality_id')
            ],
            [
                'name'     => 'residence_country_id',
                'contents' => $request->input('residence_country_id')
            ],
            [
                'name'     => 'passport_number',
                'contents' => $request->input('passport_number')
            ],
            [
                'name'     => 'passport_expiry_date',
                'contents' => $request->input('passport_expiry_date')
            ],
            [
                'name'     => 'gender',
                'contents' => $request->input('gender')
            ],
            [
                'name'     => 'occupation_business',
                'contents' => $request->input('occupation_business')
            ],
            [
                'name'     => 'job_title',
                'contents' => $request->input('job_title')
            ],
            [
                'name'     => 'employer_business_name',
                'contents' => $request->input('employer_business_name')
            ],
            [
                'name'     => 'employer_address',
                'contents' => $request->input('employer_address')
            ],
            [
                'name'     => 'applicant_annual_income_usd',
                'contents' => $request->input('applicant_annual_income_usd')
            ],
            [
                'name'     => 'currency_ids',
                'contents' => implode(',', $request->input('currency'))
            ],
            [
                'name'     => 'passport_copy',
                'contents' => fopen($request->file('passport_file'), 'r'),
                'filename' => $request->file('passport_file')->getClientOriginalName(),
            ],
            [
                'name'     => 'selfie_photo',
                'contents' => $request->input('photo-url')
            ],
            [
                'name'     => 'signature_photo',
                'contents' => $signaturePhotoStream ?? fopen($signaturePhoto, 'r'),
                'filename' => 'signature.png',
            ],

        ];

        try {
            ClientHelper::onboardingRegisterClient($baseUrl, $endPointMapping[$client->status], $apiKey, $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            $message = "Internal Server Error, please try again later or contact the Tech team.";
            return redirect(url()->previous())->with('error', $message)->with('formnumber', 0);
        }

        return redirect(url()->previous())->with('success', 'Information submitted successfully')->with('formnumber', 5);
    }

    public function storeClient(Request $request)
    {
        $request->validate([

            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            /*             'g-recaptcha-response' => ['required', 'string'],*/
            'account_type' => ['required', 'string', 'in:individual,company'],
            'country_code_id' => ['required', 'integer', 'exists:countries,id'],
            'mobile_number' => ['required', 'numeric'],
        ]);
        $credentials = $request->only('email', 'password');

        $user = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
        ]);

        // create client account
        ClientHelper::registerClient($request, $user);

        // assign draft client role
        $user->assignRole("draft client");

        // assign draft client role
        $user->assignRole("draft client");

        foreach ($user->roles as $role) {
            if ($role->name != "draft client") {
                $user->removeRole($role->name);
            }
        }

        $onboarding = new OnboardingHelper();

        $onboarding->loginAPI($request->input('email'), $request->input('password'));

        // client verification via otp
        if (Auth::attempt($credentials)) {
            EmailNotification::sendOTP();
            return redirect()->route('2fa.index');
        }
    }

    public function worldCheckReportRequest(Request $request)
    {
        try {
            if (!WorldCheckConfigService::isEnabled()) {
                return redirect(url()->previous())->with('error', 'World-Check is disabled. Enable the WorldCheck module in System Settings.');
            }

            $client_id = $request->get('client_id');
            $director_id = $request->get('director_id');
            $result = false;

            /*    if (!SanctionsReport::where('client_id', $client_id)->first()) { */

            $client = Client::find($request->get('client_id'));
            Log::channel('worldchecklog')->info('Client Name: ' . $client->name);

            WorldCheckHelper::setLogContext([
                'client_id' => $client->id,
                'client_type' => $client->type,
                'client_name' => $client->name,
            ]);

            $targetStatuses = ['Approved', 'Pending', 'Rejected'];
            //dd($request->get('client_id'));
            if (in_array($client->status, $targetStatuses)) {
                $worldcheckHelper = new WorldCheckHelper();
                $sanctionFields['is_suspicious'] = false;
                $sanctionFields['user_id'] = auth()->id();
                $sanctionFields['worldCheck'] = true;
                $result = null;

                //dd($client);
                if ($client->type == 'individual' || !empty($director_id)) {
                    Log::channel('worldchecklog')->info('Client Type: ' . $client->type . ' | Director_ID: ' . $director_id);

                    if (!empty($director_id)) {
                        Log::channel('worldchecklog')->info('Director ID not Empty | Director_ID: ' . $director_id);

                        if ($director_id == 'contact') {
                            Log::channel('worldchecklog')->info('Director ID = contact | Director_ID: ' . $director_id);

                            $entity = $client->company->contact;
                            $sanctionFields['name'] = $entity->name;
                            $sanctionFields['passport_expiry_date'] = $entity->passport_expiry;
                        } else {
                            Log::channel('worldchecklog')->info('Director ID != contact | Director_ID: ' . $director_id);

                            $entity = ClientDirector::where("id", $director_id)
                                ->where('client_id', $client_id)
                                ->first();
                            $sanctionFields['name'] = $entity->name;
                            $sanctionFields['passport_expiry_date'] = $entity->passport_expiry;
                        }
                        WorldCheckHelper::setLogContext(['entity_label' => $sanctionFields['name']]);
                        //dd($sanctionFields);

                    } else {
                        Log::channel('worldchecklog')->info('Director ID Empty ----- Using Client Individual ----- | Client Name: ' . $client->name);

                        $entity = ClientIndividual::where("client_id", $client->id)->first();
                        $sanctionFields['name'] = $client->name;
                        $sanctionFields['passport_expiry_date'] = $entity->passport_expiry_date;
                    }

                    $sanctionFields['entity_type'] = 'individual';
                    $sanctionFields['email'] = $entity->email_address;
                    $nationality = Country::find($entity->nationality_id);

                    if (!$nationality) {
                        throw new Exception("Missing Nationality! Please fill in the details for this entity first.");
                    }

                    $sanctionFields['nationality_iso3'] = $nationality->iso3;
                    $sanctionFields['citizenship_iso3'] = $nationality->iso3;
                    $sanctionFields['passport_number'] = $entity->passport_number;
                    $sanctionFields['gender'] = $entity->gender;
                    $sanctionFields['dob'] =  $entity->dob;

                    $result = $worldcheckHelper->screenIndividualWithPassport($sanctionFields);
                } else {
                    Log::channel('worldchecklog')->info('Director ID Empty Client Type Company | Client Name: ' . $client->name);

                    $sanctionFields['entity_type'] = 'company';
                    $sanctionFields['nationality_iso3'] = null;
                    $sanctionFields['name'] = $client->name;
                    $result = $worldcheckHelper->screenEntity($sanctionFields);
                }

                $sanctionFields['w_response'] = serialize($result);

                if (isset($result->outstandingActions)) {
                    if ($result->outstandingActions) {
                        $sanctionFields['is_suspicious'] = 1;
                    }
                }

                unset($sanctionFields['nationality_iso3']);
                unset($sanctionFields['citizenship_iso3']);

                Sanctions::updateOrCreate($sanctionFields);

                //getting the report id
                // $reportResult = $worldcheckHelper->getReportID($result->caseId);
                if (empty($director_id)) {

                    $sanctionReport = SanctionsReport::where('client_id', $request->get('client_id'))->whereNull('director_id')->whereNull('contact_id')->first() ?? new SanctionsReport();
                } else {
                    if ($director_id == 'contact') {
                        //if owner
                        $sanctionReport = SanctionsReport::where('client_id', $request->get('client_id'))->whereNull('director_id')->where('contact_id', $client->company->contact->id)->first() ?? new SanctionsReport();
                        $sanctionReport->contact_id = $client->company->contact->id;
                    } else {
                        $sanctionReport = SanctionsReport::where('client_id', $request->get('client_id'))->where('director_id', $director_id)->first() ?? new SanctionsReport();
                        $sanctionReport->director_id = $director_id;
                    }
                }

                $sanctionReport->client_id = $client_id;
                if ($sanctionReport->url != null) {
                    S3Helper::fileDelete($sanctionReport->url);
                }

                $sanctionReport->case_system_id = $result->caseId;
                $sanctionReport->case_report_id = null;
                $sanctionReport->requested = false;
                $sanctionReport->status = 'Pending';
                $sanctionReport->url = null;
                $sanctionReport->save();
            }
            /*  } else {
                $result = true;
            } */

            /*   if ($result) {
                $this->worldCheckReportGetStatusAndSave($client_id, $director_id);
            } */

            return redirect(url()->previous())->with('success', 'Client KYC Report has been requested successfully!')->with('formnumber', 3);
        } catch (Exception $e) {
            //dd($e);
            return redirect(url()->previous())->with('error', $e->getMessage());
        } finally {
            WorldCheckHelper::clearLogContext();
        }
    }

    public function requestClientKYC(Request $request)
    {
        try {
            $client = Client::find($request->get("client_id"));
            /*  $client->status = "Liveness Pending"; */
            // $user = ClientHelper::getUserByClientId($client->id);
            $user = User::where("email", $client->individual->email_address)->first();

            if ($user && $client->users[0]->user->hasRole('API')) {
                $user->removeRole("client");
                $user->assignRole("draft client");
                // $hash = str_replace('/','',Hash::make($user->id . '-' . time()));
                // $kyc_req  =  new KycRequest();
                // $kyc_req->email = 'null';
                // $kyc_req->person_name = 'null';
                // $kyc_req->created_by = Auth::user()->id;
                // $kyc_req->expiry = date("Y-m-d H:i:s", strtotime("+1 week"));
                // $kyc_req->hash = $hash;
                // $kyc_req->client_id =  $client->id;
                // $kyc_req->save();
                // //$request = KycRequest::where("hash", $hash)->first();

                // EmailNotification::notifyClientKYCRequest($client, $hash);

                // return redirect(url()->previous())->with('success', 'Client KYC has been requested successfully!')->with('formnumber', 3);
            }

            if ($client) {
                $client->is_kyc_requested = true;
                $client->save();
                $hash = str_replace('/', '', Hash::make($client->id . '-' . time()));
                $kyc_req  =  new KycRequest();
                $kyc_req->created_by = Auth::user()->id;
                $kyc_req->expiry = date("Y-m-d H:i:s", strtotime("+4 week"));
                $kyc_req->hash = $hash;
                $kyc_req->client_id =  $client->id;
                $kyc_req->request_selfie =  1;
                $kyc_req->save();

                EmailNotification::notifyClientKYCRequest($client, $hash);

                return redirect(url()->previous())->with('success', 'Client KYC has been requested successfully!')->with('formnumber', 3);
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__, $request);

            return redirect(url()->previous())->with('error', $e);
        }
    }

    public function compareFaces(Request $request)
    {

        try {

            $client = Client::find($request->get('client_id'));
            //dd($client);
            $director_id  = '';

            if (!empty($request->get('director_id'))) {
                $director_id = $request->get('director_id');
            }

            ClientHelper::compareClientPhotos($request->get('selfie_url_id'), $request->get('passport_url_id'), $client, $director_id);
        } catch (Exception $e) {
            Log::error('Exception Thrown | Exception Message: ' . $e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());

            return redirect(url()->previous())->with('error', "Client passport and selfie photos didn't quite match.");
        }

        return redirect(url()->previous())->with('success', 'Client photos has been compared successfully!')->with('formnumber', 3);
    }

    public function onboardingFeeCreatePaymentLink(Request $request)
    {
        $request->validate([

            'currency_id' => ['required'],
        ]);
        try {
            $client = Client::find($request->get("client_id"));

            if ($client->status == 'Approved') {
                $onboardingFeeHelper = new OnboardingFeeHelper();
                $onboardingFeeRecord = OnboardingFeeRecords::where("client_id", $client->id)->first();

                if ($onboardingFeeRecord && (!$onboardingFeeRecord->transaction_status || !$onboardingFeeRecord->transaction_status == "Declined" || !$onboardingFeeRecord->transaction_status == "Refunded")) {
                    EmailNotification::notifyClientPaymentRequest($client, $onboardingFeeRecord->payment_link_url);
                } else {
                    $result = $onboardingFeeHelper->createPaymentLink($client, $request->get("email_address"), $request->get("currency_id"));

                    EmailNotification::notifyClientPaymentRequest($client, $result->PaymentLinkUrl);
                }

                return redirect(url()->previous())->with('success', 'Client Payment has been requested successfully!')->with('formnumber', 7);
            }

            return redirect(url()->previous())->with('error', 'This client is not Approved.');
        } catch (Exception $e) {

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function getSanctionReport($client_id, $director_id = '')
    {
        if (empty($director_id)) {
            $sanctionReport = SanctionsReport::where('client_id', $client_id)->whereNull('director_id')->whereNull('contact_id')->first();
        } else {
            if ($director_id == 'contact') {
                //if owner
                $client = Client::find($client_id);
                $sanctionReport = SanctionsReport::where('client_id', $client_id)->whereNull('director_id')->where('contact_id', $client->company->contact->id)->first() ?? new SanctionsReport();
            } else {
                $sanctionReport = SanctionsReport::where('client_id', $client_id)->where('director_id', $director_id)->first();
            }
        }
        return $sanctionReport;
    }

    public function worldCheckReportGetStatusAndSave(Request $request)
    {
        try {
            if (!WorldCheckConfigService::isEnabled()) {
                return redirect(url()->previous())->with('error', 'World-Check is disabled. Enable the WorldCheck module in System Settings.');
            }

            $client_id = $request->get('client_id');
            $director_id = $request->get('director_id');
            $client = Client::find($client_id);
            if ($client) {
                WorldCheckHelper::setLogContext([
                    'client_id' => $client->id,
                    'client_type' => $client->type,
                    'client_name' => $client->name,
                ]);
            }

            $worldcheckHelper = new WorldCheckHelper();

            $sanctionReport = $this->getSanctionReport($client_id, $director_id);

            $reportId = $sanctionReport->case_report_id;

            if ($reportId == null) {
                $reportResult = $worldcheckHelper->getReportID($sanctionReport->case_system_id);
                $reportId = $reportResult->reportId;
            }

            $sanctionReport->case_report_id = $reportId;
            // Getting report status
            $result = $worldcheckHelper->getReportStatus($reportId);

            $progressStatus = $result ? $result->progressStatus : 'IN_PROGRESS';

            $sanctionReport->status = $progressStatus;
            $sanctionReport->save();

            if ($sanctionReport->url != null) {
                return redirect(url()->previous())->with('success', 'Client Worldcheck Report has been downloaded successfully!')->with('formnumber', 3);
            }

            if ($progressStatus == "COMPLETED") {

                $client = Client::find($client_id);

                $url = $worldcheckHelper->downloadReport($sanctionReport->case_report_id, $client);
                $sanctionReport->url = $url;
                $sanctionReport->save();

                return redirect(url()->previous())->with('success', 'Client Worldcheck Report has been fetched successfully!')->with('formnumber', 3);
            }
            return redirect(url()->previous())->with('error', "Report Status is " . $progressStatus);
        } catch (Exception $e) {
            // Handle other exceptions
            return redirect(url()->previous())->with('error', $e->getMessage());
        } finally {
            WorldCheckHelper::clearLogContext();
        }
    }

    public function onboardingFeeFindTransaction(Request $request)
    {
        try {

            $onboardingFee = new OnboardingFeeHelper();

            $result = $onboardingFee->findPaymentLinkTransaction($request->get("client_id"));
            if ($result->IsSucceeded) {
                return redirect(url()->previous())->with('success', 'Transaction has been found successfully!')->with('formnumber', 7);
            }

            return redirect(url()->previous())->with('error', 'Transaction Not Found');
        } catch (Exception $e) {

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function onboardingFeeRefundPayment(Request $request)
    {
        try {

            $onboardingFee = new OnboardingFeeHelper();

            $onboardingFee->onboardingFeeRefundPayment($request->get("client_id"));

            return redirect(url()->previous())->with('success', 'payment has been refunded successfully!')->with('formnumber', 7);
        } catch (Exception $e) {

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function worldCheckPassportScreeningReportGetStatusAndSave(Request $request)
    {
        try {
            if (!WorldCheckConfigService::isEnabled()) {
                return redirect(url()->previous())->with('error', 'World-Check is disabled. Enable the WorldCheck module in System Settings.');
            }

            $caseID = $request['case_id'];

            $sanctions = Sanctions::find($caseID);
            if ($sanctions) {
                WorldCheckHelper::setLogContext([
                    'entity_label' => $sanctions->name,
                ]);
            }

            // Instantiate the helper class
            $worldcheckHelper = new WorldCheckHelper();

            // Getting report status
            $result = $worldcheckHelper->getReportStatus($sanctions->passport_case_id);

            // if status is failed thats means we need to contact the worldcheck support
            if ($result->progressStatus != "COMPLETED") {
                return redirect(url()->previous())->with('error', $result->progressStatus);
            }

            if ($sanctions->report_url != null) {
                return redirect(url()->previous())->with('success', 'Client Worldcheck Report has been downloaded successfully!')->with('formnumber', 3);
            }

            // Instantiate the helper class
            $worldcheckHelper = new WorldCheckHelper();

            $url = $worldcheckHelper->downloadReport($sanctions->passport_case_id, null, $caseID);
            $sanctions->report_url = $url;
            $sanctions->save();

            return redirect(url()->previous())->with('success', 'Client Worldcheck Report has been fetched successfully!')->with('formnumber', 3);
        } catch (Exception $e) {
            // Handle other exceptions
            return redirect(url()->previous())->with('error', $e->getMessage());
        } finally {
            WorldCheckHelper::clearLogContext();
        }
    }

    public function userClients()
    {
        $user_clients = ClientUser::all();
        $clients = Client::all();
        $users = User::all();

        return view("client-management.client-linked-users", compact('user_clients', 'clients', 'users'));
    }

    public function updateMaintenaceFee($id, $maintenance_fee_amount)
    {
        $account = Account::find($id);

        if (Auth::user()->can("edit client")) {
            $account->maintenance_fee = $maintenance_fee_amount;
            $account->save();

            return redirect(url()->previous())->with('success', "Maintenance fee updated successfully.");
        } else {
            return redirect(url()->previous())->with('error', "You don't have permission to update maintenance fee.");
        }
    }

    public function updateClientRiskRating(Request $request)
    {
        $this->validate($request, [
            'scoring_data' => 'required|array',
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $clientId = $request->get('client_id');
            $scoringData = $request->get('scoring_data');
            $remarks = $request->get('remarks');
            $totalRiskScore = $this->calculateTotalRiskScore($scoringData);

            if ($totalRiskScore > 100) {
                return back()->with('error', 'Total risk score cannot be more than 100.');
            }

            $latest = ClientRiskRating::where('client_id', $clientId)->where('status', 'active')->first();
            $newData = json_encode($scoringData);
            if ($latest && empty($latest->scoring_data)) {
                $latest->delete();
                $latest = null;
            }
            if ($latest && $latest->scoring_data === $newData && $latest->risk_score == $totalRiskScore && $latest->remarks == $remarks) {
                return back()->with('info', 'No changes detected in risk rating.');
            }

            // Archive the existing active record
            if ($latest) {
                $latest->status = 'archive';
                $latest->save();
            }

            // Create new active record
            ClientRiskRating::create([
                'client_id' => $clientId,
                'scoring_data' => $newData,
                'risk_score' => $totalRiskScore,
                'remarks' => $remarks,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'status' => 'active',
            ]);

            return redirect()->back()->with('success', 'Client Risk Rating updated successfully!');
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            return redirect(url()->previous())->with('error', "An error occurred while updating the client risk rating.");
        }
    }

    private function calculateTotalRiskScore($data)
    {
        $total = 0;

        foreach ($data as $value) {
            if (is_array($value)) {
                if (isset($value['score']) && is_numeric($value['score'])) {
                    $total += (float) $value['score'];
                }
                $total += $this->calculateTotalRiskScore($value);
            }
        }

        return $total;
    }


    public function downloadRiskRating($clientId, $recordId = null)
    {
        $clientRiskRating = $recordId ? ClientRiskRating::with('createdBy', 'client')->findOrFail($recordId)
            : ClientRiskRating::where('client_id', $clientId)->where('status', 'active')->with('createdBy', 'client')->first();

        if (!$clientRiskRating) {
            return redirect()->back()->with('error', 'Client Risk Rating not found.');
        }

        $user = $clientRiskRating->createdBy;
        $client = $clientRiskRating->client;

        $scoringData = json_decode($clientRiskRating->scoring_data, true);
        $sectionScores = $this->calculateSectionScores($scoringData);

        $currentDate = now()->format('Y-m-d');

        $view = view('pdfs.client-management.client-risk-rating', compact('clientRiskRating', 'user', 'client', 'scoringData', 'currentDate', 'sectionScores'));
        //return $view;
        $pdf = PDF::loadHTML($view)->setPaper('a4');

        return $pdf->download($client->name . ' - Risk-Scoring Report.pdf');
    }

    public function fingateCardCreate(Request $request)
    {
        try {
            $request->validate([
                'client_id' => 'required|integer',
            ]);

            $fingateHelper = new FingateHelper();

            $fingateHelper->createOrAssignCard($request->get("client_id"));

            return redirect(url()->previous())->with('success', 'Virtual Card has been created successfully!')->with('formnumber', 8);
        } catch (Exception $e) {

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function fingateFindCard(Request $request)
    {
        try {
            $request->validate([
                'card_id' => 'required|integer',
            ]);

            $fingateHelper = new FingateHelper();

            $data = $fingateHelper->findCardAPI($request->get("card_id"));
            // dd($data);
            return redirect(url()->previous())->with('success', 'Virtual Card has been created successfully!')->with('formnumber', 8);
        } catch (Exception $e) {

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function getRiskRatingHistory($clientId)
    {
        $history = ClientRiskRating::where('client_id', $clientId)
            ->where('status', 'archive')
            ->whereNotNull('scoring_data')
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'created_at', 'created_by']);
        return response()->json($history);
    }

    public function getRiskRatingRecord($recordId)
    {
        $record = ClientRiskRating::findOrFail($recordId);
        $data = json_decode($record->scoring_data, true);
        $data['remarks'] = $record->remarks;
        $data['risk_score'] = $record->risk_score;
        return response()->json($data);
    }

    private function calculateSectionScores($data)
    {
        $sectionScores = [];

        foreach ($data as $section => $items) {
            $total = 0;

            foreach ($items as $item) {
                if (isset($item['score']) && is_numeric($item['score'])) {
                    $total += (float)$item['score'];
                }
            }

            $sectionScores[$section] = $total;
        }

        return $sectionScores;
    }

    public function overrideVirtualBankProvider(Request $request)
    {
        try {
            $request->validate([
                'account_id' => 'required|integer',
                'virtual_bank_provider' => 'required|string'
            ]);

            $account = Account::find($request->get('account_id'));

            if (!$account) {
                return redirect(url()->previous())->with('error', 'Account not found.');
            }

            $account->virtual_bank_provider = $request->get('virtual_bank_provider');
            $account->save();

            return redirect(url()->previous())->with('success', 'Virtual bank provider has been updated successfully!')->with('formnumber', 6);
        } catch (Exception $e) {
            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }
}
