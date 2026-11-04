<?php

namespace App\Http\Controllers;

use App\Helpers\EmailNotification;
//use App\Helpers\SanctionsHelper;
use App\Helpers\WorldCheckHelper;
use App\Models\Country;
use App\Models\Sanctions;
use App\Models\TRRequest;
use App\Helpers\Common;
use App\Models\User;
use App\Models\Account;
use App\Services\WorldCheckConfigService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Models\Module;

class SanctionsController extends Controller
{
    public $compliance_module_is_enabled;
    public function __construct()
    {
        $this->compliance_module_is_enabled = in_array("Compliance", \App\Helpers\Common::getEnabledModules());
    }
    public function index()
    {
        $nationalities = Country::get();
        return view("compliance-management.new-screening", compact('nationalities'));
    }

    public function store(Request $request)
    {

        $this->validate($request, [
            'entity-type' => 'required',
            //  'search-source' => 'required'
        ]);

        if (request('entity-type') == 'individual') {
            $this->validate($request, [
                'screen-by' => 'required',
                'last-name' => 'required'
            ]);

            if (in_array('screen-by-passport', $request['screen-by'])) {
                $this->validate($request, [
                    'passport_issuingState' => 'required',
                    'passport_expiry_date' => 'required',
                    'passport_number' => 'required',
                    'nationality_iso' => 'required',
                    'dob' => 'required',
                    'gender' => 'required',
                ]);
            }
        }

        if (!$this->compliance_module_is_enabled) {
            return redirect()->back()->with("error", "Compliance module is disabled");
        }

        if (!WorldCheckConfigService::isEnabled()) {
            return redirect()->back()->with("error", "World-Check is disabled. Enable the WorldCheck module in System Settings.");
        }

        $sanctionFields['screen_by_name'] = isset($request['screen-by']) && in_array('screen-by-name', $request['screen-by']);
        $sanctionFields['screen_by_passport'] = isset($request['screen-by']) && in_array('screen-by-passport', $request['screen-by']);
        $sanctionFields['user_id'] = auth()->id();
        $sanctionFields['name'] = request('first-name') . " " . request('last-name');
        $sanctionFields['first-name'] = request('first-name');
        $sanctionFields['last-name'] = request('last-name');
        $sanctionFields['entity_type'] = request('entity-type');
        $sanctionFields['email'] = request('email_address');
        $sanctionFields['address'] = request('address');
        $sanctionFields['nationality_iso2'] = $request['nationality_iso'];
        $sanctionFields['citizenship_iso2'] =  $request['citizenship_iso'];
        $sanctionFields['place_of_birth_iso2'] =  $request['place_of_birth_iso'];
        $sanctionFields['country_residence_iso2'] =  $request['country_residence_iso'];
        $sanctionFields['nationality_iso3'] = $request['nationality_iso3'];
        $sanctionFields['citizenship_iso3'] = $request['citizenship_iso3'];
        $sanctionFields['place_of_birth_iso3'] = $request['place_of_birth_iso3'];
        $sanctionFields['country_residence_iso3'] = $request['country_residence_iso3'];
        $sanctionFields['passport_number'] = request('passport_number');
        $sanctionFields['gender'] = request('gender');
        $sanctionFields['passport_issuing_state'] = request('passport_issuingState');
        $sanctionFields['dob'] = is_null($request['dob']) ? null : Carbon::createFromFormat('d/m/Y', $request['dob'])->format('Y-m-d');
        $sanctionFields['passport_expiry_date'] = is_null($request['passport_expiry_date']) ? null :  Carbon::createFromFormat('d/m/Y', $request['passport_expiry_date'])->format('Y-m-d');
        $sanctionFields['is_suspicious'] = 0;

        // $searchSource = request('search-source');
        $entityType = request('entity-type');

        WorldCheckHelper::setLogContext([
            'entity_label' => trim((string) $sanctionFields['name']),
        ]);

        $worldcheckHelper = new WorldCheckHelper();

        //sanctions.io api checked
        try {

            /*    if (in_array('sanctions_io', $searchSource)) {
                $sanctionFields['sanctions_io'] = true;
                $dob = is_null($request['dob']) ? null : Carbon::createFromFormat('d/m/Y', $request['dob'])->format('Y M j');

                $sanctionsHelper = new SanctionsHelper();

                $params = [];
                $params['name'] = $sanctionFields['name'];
                $params['entity_type'] = $sanctionFields['entity_type'];
                $params['date_of_birth'] = $dob;
                $params['nationality'] = $sanctionFields['nationality_iso2'];
                $params['email'] = $sanctionFields['email'];
                $params['address'] = $sanctionFields['address'];
                $params['citizenship'] = $sanctionFields['citizenship_iso2'];
                $params['place_of_birth'] = $sanctionFields['place_of_birth_iso2'];
                $params['country_residence'] = $sanctionFields['country_residence_iso2'];

                $result = $sanctionsHelper->getSanctionsForUsers($params);

                $sanctionFields['s_response'] = serialize($result);
                $confidanceScore = 0;

                if (isset($result->results)) {
                    foreach ($result->results as $result) {
                        $sanctionName = str_replace(' ', '', $sanctionFields['name']);
                        $resultName = str_replace(' ', '', $result->name);
                        if ($sanctionName == $resultName) {
                            if ($result->confidence_score > $confidanceScore) {

                                $confidanceScore = $result->confidence_score;
                            }
                        }
                    }

                    $sanctionFields['is_suspicious'] = $confidanceScore >= 0.7 ? 1 : 0;
                }
            }
 */
            //worldcheck checked
            //if (in_array('worldCheck', $searchSource)) {
            $sanctionFields['worldCheck'] = true;
            if ($entityType == 'individual') {
                if ($sanctionFields['screen_by_passport']) {
                    $result = $worldcheckHelper->screenIndividualWithPassport($sanctionFields);
                    $sanctionFields['w_passport_response'] = serialize($result);
                    $reportResult = $worldcheckHelper->getReportID($result->caseId);
                    $sanctionFields['passport_case_id'] = $reportResult->reportId;

                    if (isset($result->outstandingActions)) {
                        if ($result->outstandingActions) {
                            $sanctionFields['is_suspicious'] = 1;
                        }
                    }
                }

                if ($sanctionFields['screen_by_name']) {
                    $result = $worldcheckHelper->screenEntity($sanctionFields);
                    $sanctionFields['w_response'] = serialize($result);
                    if (isset($result->outstandingActions)) {
                        if ($result->outstandingActions) {
                            $sanctionFields['is_suspicious'] = 1;
                        }
                    }
                }
            } else {
                $result = $worldcheckHelper->screenEntity($sanctionFields);
                $sanctionFields['w_response'] = serialize($result);

                if (isset($result->outstandingActions)) {
                    if ($result->outstandingActions) {
                        $sanctionFields['is_suspicious'] = 1;
                    }
                }
            }
            //    }
        } catch (Exception $e) {
            WorldCheckHelper::clearLogContext();

            return redirect()->back()->with('error', $e->getMessage());
        }

        WorldCheckHelper::clearLogContext();

        $id = Sanctions::create($sanctionFields);
        return redirect('/dashboards/compliance-management/detailed-case/' . $id->id);
    }

    public function showAllCases(Request $request)
    {
        if (!$this->compliance_module_is_enabled) {
            return redirect()->back()->with("error", "Compliance module is disabled");
        }
        $cases = Sanctions::select(
            "sanctions.id",
            "sanctions.name",
            "sanctions.entity_type",
            "sanctions.screen_by",
            "sanctions.created_at",
            "sanctions.is_suspicious",
            "sanctions.worldCheck",
            "sanctions.resolved",
            "sanctions.blocked",
            "sanctions.blocked_by",
            "sanctions.resolved_by",
            "sanctions.resolved_at",
            "sanctions.sanctions_io",
            "creator.name as creator",
            "resolvedBy.name as resolved_by",
            "blockedBy.name as blocked_by"
        )->whereNull('sanctions.case_id')
            ->leftJoin("users as creator", "sanctions.user_id", "=", "creator.id")
            ->leftJoin("users as resolvedBy", "sanctions.resolved_by", "=", "resolvedBy.id")
            ->leftJoin("users as blockedBy", "sanctions.blocked_by", "=", "blockedBy.id")
            ->orderBy('sanctions.created_at', 'desc');

        $perPage = $request->get("per_page");

        if ($request->get('search_string')) {
            $cases->where('sanctions.name', 'LIKE', '%' . $request->get('search_string') . '%');
        }

        if ($perPage == -1 || ($request->get('search_string') && $cases->count() < 10)) {
            $perPage = $cases->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }

        $cases = $cases->paginate($perPage);

        return view("compliance-management.cases", compact('cases', 'request'));
    }

    // public function flaggedTransactions(Request $request)
    // {
    //     if (!$this->compliance_module_is_enabled) {
    //         return redirect()->back()->with("error", "Compliance module is disabled");
    //     }
    //     // if ($nid) {
    //     //     Common::readNotification($nid);
    //     // }

    //     // $client_ids = Common::getClientIdsByUserId(Auth::user()->id);
    //    // $accounts = Account::whereIn("client_id", $client_ids)->pluck("id")->toArray();

    //     $transactions = TRRequest::whereIn("status", ["Pending", "Initial Review"])
    //         ->where("crypto_amount", null)
    //         ->where("compliance_status", 'MODERATION')
    //         //->where("status", 'pending')
    //         ->whereIn("transaction_nature", ["Cheque", "TT", "Internal"])
    //         ->orderBy("created_at", "DESC")
    //         ->get();

    //     return view("compliance-management.flagged-list", compact('transactions'));

    //    // return view("compliance-management.cases", compact('cases', 'request'));
    // }
public function flaggedTransactions(Request $request)
{
    if (!$this->compliance_module_is_enabled) {
        return redirect()->back()->with("error", "Compliance module is disabled");
    }

    $search = $request->get('search_string');
    $sortBy = $request->get('sort_by', 'id');
    $sortDir = $request->get('sort_dir', 'desc');
    $perPage = $request->get('per_page', 10);

   $transactions = TRRequest::with('account')
    ->leftJoin('accounts', 'accounts.id', '=', 'account_transaction_requests.account_id')
    ->select('account_transaction_requests.*', 'accounts.title as account_title')
    ->whereIn('status', ['Pending', 'Initial Review'])
    ->whereNull('crypto_amount')
    ->where('compliance_status', 'MODERATION')
    ->whereIn('transaction_nature', ['Cheque', 'TT', 'Internal']);

    // Search
    if ($search) {
        $transactions->where(function ($q) use ($search) {
            $q->where('remarks', 'LIKE', "%$search%")
                ->orWhere('amount', 'LIKE', "%$search%")
                ->orWhere('transaction_nature', 'LIKE', "%$search%")
                ->orWhere('transaction_type', 'LIKE', "%$search%")
                 ->orWhere('remarks', 'LIKE', "%$search%")
                ->orWhere('accounts.title', 'LIKE', "%$search%");
        });
    }

    // Sorting
    if ($sortBy === 'account_title') {
        $transactions->orderBy('accounts.title', $sortDir);
    } else {
        $transactions->orderBy($sortBy, $sortDir);
    }

    // Pagination
    if ($perPage == -1) $perPage = $transactions->count();

    $transactions = $transactions->paginate($perPage)->appends($request->all());

    return view('compliance-management.flagged-list', [
        'transactions' => $transactions,
        'perPage' => $transactions->perPage(),
        'currentPage' => $transactions->currentPage(),
        'total' => $transactions->total(),
        'request' => $request,
        'search_string' => $search,
    ]);
}






    public function showCase($id)
    {
        if (!$this->compliance_module_is_enabled) {
            return redirect()->back()->with("error", "Compliance module is disabled");
        }
        $case = Sanctions::find($id);
        $case_worldCheck = unserialize($case->w_response);
        $case_worldCheck_passport = unserialize($case->w_passport_response);
        // $case_sanctionsIo = unserialize($case->s_response);

        return view("compliance-management.detailed-case", compact('case'), ['case_worldCheck' => $case_worldCheck, 'case_worldCheck_passport' => $case_worldCheck_passport]);
    }

    public function showTransactionsCases(Request $request)
    {
        if (!$this->compliance_module_is_enabled) {
            return redirect()->back()->with("error", "Compliance module is disabled");
        }
        $cases = Sanctions::select(
            "sanctions.id",
            "sanctions.name",
            "sanctions.entity_type",
            "sanctions.screen_by",
            "sanctions.created_at",
            "sanctions.is_suspicious",
            "sanctions.worldCheck",
            "sanctions.resolved",
            "sanctions.blocked",
            "sanctions.blocked_by",
            "sanctions.resolved_by",
            "sanctions.resolved_at",
            "sanctions.case_id",
            "sanctions.sanctions_io",
            "creator.name as creator",
            "resolvedBy.name as resolved_by",
            "blockedBy.name as blocked_by"
        )->whereNotNull('sanctions.case_id')
            ->leftJoin("users as creator", "sanctions.user_id", "=", "creator.id")
            ->leftJoin("users as resolvedBy", "sanctions.resolved_by", "=", "resolvedBy.id")
            ->leftJoin("users as blockedBy", "sanctions.blocked_by", "=", "blockedBy.id")
            ->orderBy('sanctions.created_at', 'desc');

        $perPage = $request->get("per_page");

        if ($request->get('search_string')) {
            $cases->where('sanctions.name', 'LIKE', '%' . $request->get('search_string') . '%')->orWhere('sanctions.case_id', 'LIKE', '%' . $request->get('search_string') . '%');
        }

        if ($perPage == -1 || ($request->get('search_string') && $cases->count() < 10)) {
            $perPage = $cases->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }

        $cases = $cases->paginate($perPage);

        return view("compliance-management.transactions-cases", compact('cases', 'request'));
    }

    public function showTransactionCase($id)
    {
        if (!$this->compliance_module_is_enabled) {
            return redirect()->back()->with("error", "Compliance module is disabled");
        }
        $case = Sanctions::find($id);
        //$case_sanctionsIo = unserialize($case->s_response);

        return view("compliance-management.transactions-detailed-case", compact('case'), ['id' => $id]);
    }

    public function transactionPDF($id)
    {
        $case = Sanctions::find($id);
        $case_sanctionsIo = unserialize($case->s_response);
        if (isset($case_sanctionsIo->results)) {
            foreach ($case_sanctionsIo->results as $resultIndex => $item) {
                if (isset($item->citizenship)) {
                    foreach ($item->citizenship as $citizenshipIndex => $isoCode) {
                        $case_sanctionsIo->results[$resultIndex]->citizenship[$citizenshipIndex] = $this->getCountryName($isoCode);
                    }
                }
                if (isset($item->nationality)) {
                    foreach ($item->nationality as $nationalityIndex => $isoCode) {
                        $case_sanctionsIo->results[$resultIndex]->nationality[$nationalityIndex] = $this->getCountryName($isoCode);
                    }
                }
            }
        }

        $view = view('pdfs.compilance.transaction-pdf', compact('case'), ['case_sanctionsIo' => $case_sanctionsIo, 'id' => $id]);
        $pdf = PDF::loadHTML($view)->setPaper('a4', 'portrait');
        return $pdf->download('Transaction Case.pdf');
    }

    public function getCountryName($isoCode)
    {
        $country = Country::where('iso', $isoCode)->first();
        $countryName = $country ? $country->name : 'Unknown Country';
        return $countryName;
    }

    public function resolveTransactionCase($id)
    {
        if (!$this->compliance_module_is_enabled) {
            return redirect()->back()->with("error", "Compliance module is disabled");
        }
        $case = Sanctions::find($id);
        $case->resolved = 1;
        $case->resolved_by = Auth::user()->id;
        $case->resolved_at = now();
        $case->blocked = 0;
        $case->blocked_by = null;
        $case->save();

        $transaction = TRRequest::where('case_id', $case->case_id)->first();

        EmailNotification::notifyFinanceManagersForTRRequestResolving($transaction, "Transaction has been resolved - Pending Approval");

        return redirect("/dashboards/compliance-management/transactions-cases")->with('success', "Case Resolved successfully.");
    }

    public function blockTransactionCase($id)
    {
        if (!$this->compliance_module_is_enabled) {
            return redirect()->back()->with("error", "Compliance module is disabled");
        }
        $case = Sanctions::find($id);
        $case->blocked = 1;
        $case->blocked_by = Auth::user()->id;
        $case->blocked_at = now();
        $case->save();

        $transaction = TRRequest::where('case_id', $case->case_id)->first();

        EmailNotification::notifyFinanceManagersForTRRequestResolving($transaction, "Transaction has been blocked");

        return redirect("/dashboards/compliance-management/transactions-cases")->with('success', "Case Blocked successfully.");
    }

    public function resolveCase($id)
    {
        if (!$this->compliance_module_is_enabled) {
            return redirect()->back()->with("error", "Compliance module is disabled");
        }
        $case = Sanctions::find($id);
        $case->resolved = 1;
        $case->resolved_by = Auth::user()->id;
        $case->resolved_at = now();
        $case->blocked = 0;
        $case->blocked_by = null;
        $case->save();

        return redirect("/dashboards/compliance-management/cases")->with('success', "Case Resolved successfully.");
    }

    public function blockCase($id)
    {
        if (!$this->compliance_module_is_enabled) {
            return redirect()->back()->with("error", "Compliance module is disabled");
        }
        $case = Sanctions::find($id);
        $case->blocked = 1;
        $case->blocked_by = Auth::user()->id;
        $case->blocked_at = now();
        $case->save();

        return redirect("/dashboards/compliance-management/cases")->with('success', "Case blocked successfully.");
    }

    public function redirectToDeepLinkingWorlCheck()
    {
        $case_id = request('case_id');
        $endPoint = request('endPoint');

        return redirect()->to('https://worldcheck.refinitiv.com/#fsp/case/' . $case_id . '/view/' . $endPoint);
    }
}
