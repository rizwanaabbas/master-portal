<?php

namespace App\Http\Controllers;

use App\Helpers\ChangeLogger;
use App\Helpers\ClientHelper;
use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Helpers\S3Helper;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Currency;
use App\Models\TRRequest;
//use App\Models\CryptoTransactionLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Rules\SupportingDocumentsRule;
use App\Helpers\BitpaceMerchantApis;
use App\Helpers\GEDMerchantApis;
use App\Helpers\NeroDataHelper;
use App\Helpers\CryptoLogHelper;
use Illuminate\Support\Facades\DB;
use App\Modules\Aml\Controllers\AmlRuleController;
use Illuminate\Support\Facades\Session;
use App\Modules\Fireblocks\Services\DepositAddressService;
use App\Modules\Fireblocks\Services\WithdrawalService;
use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Models\FireblocksTransaction;
use App\Modules\Fireblocks\Models\Asset;
use App\Modules\Fireblocks\Models\Network;
use App\Modules\Fireblocks\Models\CryptoBeneficiary;
use App\Modules\Fireblocks\Support\TrFireblocksBeneficiaryResolver;
use App\Modules\Fireblocks\Services\FireblocksConfigService;

use Illuminate\Support\Facades\Log;
use Exception;

class CryptoTransactionController extends Controller
{
    private $GEDhelper;
    public function __construct()
    {
        // $this->middleware('permission:crypto transactions', ['only' => 'index']);
        $this->middleware('permission:crypto transaction list', ['only' => 'index']);
        $this->middleware('permission:crypto pending transactions list', ['only' => ['getPendingTransactionRequests', 'getRejectedTransactionRequests', 'getTransactionRequests']]);
        // $this->middleware('permission:add crypto transaction', ['only' => ['addCheque', 'addTT', 'addInternal', 'storeCheque', 'storeTT', 'storeInternal']]);
        $this->middleware('permission:edit crypto transaction', ['only' => ['editCryptoTransaction', 'updateCryptoTransaction']]);
        $this->middleware('permission:delete crypto transaction', ['only' => 'deleteCryptoPendingTransaction']);
        $this->middleware('permission:view crypto transaction', ['only' => ['viewCryptoTransactionRequest', 'printTransactionRequest', 'fireblocksBeneficiaryModal']]);
        $this->middleware('permission:approve crypto transaction', ['only' => ['approveCryptoTransactionRequest', 'approveWithdrawCryptoTransactionRequest', 'rejectCryptoTransaction']]);
        // $this->GEDhelper = new GEDMerchantApis();
    }

    public function index(Request $request)
    {
        $modeOptions = ['Buy', 'Sell', 'Deposit', 'Withdraw', 'Swap'];
        $typeOptions = ['Credit', 'Debit'];
        $statusOptions = ['Approved', 'Pending', 'Rejected', 'Cancelled', 'Needs Review'];

        if ($request->ajax()) {
            $query = AccountTransaction::query()
                ->select(
                    'account_transactions.id',
                    'account_transactions.crypto_system',
                    'account_transactions.transaction_type',
                    'account_transactions.transaction_nature',
                    'account_transactions.amount',
                    'currency.is_crypto as currency_is_crypto',
                    'account.title',
                    'account_transactions.status',
                    'account.account_number',
                    'account_transactions.transaction_date',
                    'user.name',
                    'account_transactions.approved_by',
                    'approver_user.name as approver'
                )
                ->where('account_transactions.is_crypto', true)
                ->leftJoin('accounts as account', 'account.id', '=', 'account_transactions.account_id')
                ->leftJoin('currencies as currency', 'currency.id', '=', 'account.account_currency')
                ->leftJoin('users as user', 'account_transactions.created_by', '=', 'user.id')
                ->leftJoin('users as approver_user', 'account_transactions.approved_by', '=', 'approver_user.id');

            $status = trim((string) $request->input('filter_status', 'Approved'));
            if ($status !== '' && strtolower($status) !== 'all') {
                $query->where('account_transactions.status', $status);
            }

            $mode = trim((string) $request->input('filter_mode', ''));
            if ($mode !== '' && strtolower($mode) !== 'all') {
                $query->where('account_transactions.transaction_nature', $mode);
            }

            $type = trim((string) $request->input('filter_type', ''));
            if ($type !== '' && strtolower($type) !== 'all') {
                $query->where('account_transactions.transaction_type', $type);
            }

            $portal = trim((string) $request->input('filter_portal', ''));
            if ($portal !== '' && strtolower($portal) !== 'all') {
                $query->where('account_transactions.crypto_system', $portal);
            }

            $dateFrom = trim((string) $request->input('filter_date_from', ''));
            if ($dateFrom !== '') {
                $query->whereDate('account_transactions.transaction_date', '>=', $dateFrom);
            }

            $dateTo = trim((string) $request->input('filter_date_to', ''));
            if ($dateTo !== '') {
                $query->whereDate('account_transactions.transaction_date', '<=', $dateTo);
            }

            $dataTable = Datatables::of($query)
                ->editColumn('amount', function ($row) {
                    return Common::formatNumber($row->amount, (bool) ($row->currency_is_crypto ?? true));
                })
                ->filter(function ($q) use ($request) {
                    $search = trim((string) $request->input('search.value'));
                    if ($search === '') {
                        return;
                    }

                    $q->where(function ($inner) use ($search) {
                        $inner->where('account_transactions.id', 'like', "%{$search}%")
                            ->orWhere('account_transactions.transaction_nature', 'like', "%{$search}%")
                            ->orWhere('account_transactions.transaction_type', 'like', "%{$search}%")
                            ->orWhere('account_transactions.crypto_system', 'like', "%{$search}%")
                            ->orWhere('account_transactions.status', 'like', "%{$search}%")
                            ->orWhere('account_transactions.amount', 'like', "%{$search}%")
                            ->orWhere('account.title', 'like', "%{$search}%")
                            ->orWhere('account.account_number', 'like', "%{$search}%")
                            ->orWhere('user.name', 'like', "%{$search}%")
                            ->orWhere('approver_user.name', 'like', "%{$search}%");
                    });
                }, true);

            $dataTable->orderColumn('account_transactions.id', function ($query, $order) {
                $query->reorder()->orderBy('account_transactions.id', $order);
            });
            $dataTable->orderColumn('account_transactions.transaction_nature', function ($query, $order) {
                $query->reorder()->orderBy('account_transactions.transaction_nature', $order);
            });
            $dataTable->orderColumn('account_transactions.transaction_type', function ($query, $order) {
                $query->reorder()->orderBy('account_transactions.transaction_type', $order);
            });
            $dataTable->orderColumn('account_transactions.amount', function ($query, $order) {
                $query->reorder()->orderBy('account_transactions.amount', $order);
            });
            $dataTable->orderColumn('account_transactions.transaction_date', function ($query, $order) {
                $query->reorder()->orderBy('account_transactions.transaction_date', $order)
                    ->orderByDesc('account_transactions.id');
            });
            $dataTable->orderColumn('account_transactions.crypto_system', function ($query, $order) {
                $query->reorder()->orderBy('account_transactions.crypto_system', $order);
            });
            $dataTable->orderColumn('account_transactions.status', function ($query, $order) {
                $query->reorder()->orderBy('account_transactions.status', $order);
            });
            $dataTable->orderColumn('user.name', function ($query, $order) {
                $query->reorder()->orderBy('user.name', $order);
            });
            $dataTable->orderColumn('approver_user.name', function ($query, $order) {
                $query->reorder()->orderBy('approver_user.name', $order);
            });

            return $dataTable->make(true);
        }

        $portalOptions = AccountTransaction::query()
            ->where('is_crypto', true)
            ->whereNotNull('crypto_system')
            ->where('crypto_system', '!=', '')
            ->distinct()
            ->orderBy('crypto_system')
            ->pluck('crypto_system')
            ->filter()
            ->values();

        return view('crypto-management.crypto-transactions.list', [
            'modeOptions' => $modeOptions,
            'typeOptions' => $typeOptions,
            'statusOptions' => $statusOptions,
            'portalOptions' => $portalOptions,
        ]);
    }

    public function editCryptoTransaction($id)
    {
        $trn = AccountTransaction::find($id);

        if (($trn->transaction_nature == "Buy" && $trn->transaction_type == "Debit") ||
            ($trn->transaction_nature == "Sell" && $trn->transaction_type == "Credit")
        ) {

            $currencies = Currency::where("is_active", true)
                ->where("is_crypto", false)
                ->select("id")
                ->get();
        } elseif (($trn->transaction_nature == "Buy" && $trn->transaction_type == "Credit") ||
            ($trn->transaction_nature == "Sell" && $trn->transaction_type == "Debit") ||
            ($trn->transaction_nature == "Withdraw") || ($trn->transaction_nature == "Deposit") ||
            ($trn->transaction_nature == "Swap")
        ) {

            $currencies = Currency::where("is_active", true)
                ->where("is_crypto", true)
                ->select("id")
                ->get();
        }

        $accounts = Account::where("is_active", true)->whereIn("account_currency", $currencies)->get();
        $changes = self::getChanges($trn);

        $transactionRequest = TRRequest::find($trn->tr_request_id);
        $crypto_transaction_id = $trn->crypto_transaction_id;
        $transaction_log = [];

        if ($crypto_transaction_id != '') {
            $transaction_log = DB::table('crypto_transactions_log')->where('transaction_id', $crypto_transaction_id)->orderBy('id', 'DESC')->get();
        }

        if ($transactionRequest) {
            $docs = $transactionRequest->docs;
        } elseif ($trn->docs->count() > 0) {
            $docs = $trn->docs;
        } else {
            $docs = [];
        }

        $needleMapping = ["Buy" => "crypto-transaction", "Deposit" => "deposit", "Sell" => "crypto-transaction", "Withdraw" => "withdraw", "Swap" => "crypto-transaction"];

        // Liquidity provider (B2c2/Aquanow) order + fee-engine breakdown for the order card.
        // Use physical availability (module code installed), not enabled state —
        // a disabled module must still let ops view historical transaction details.
        $b2c2Order = null;
        $feeCharges = collect();
        if (Common::isModuleAvailable('Liquidity') && in_array($trn->crypto_system, ['B2c2', 'Aquanow'], true)) {
            $b2c2Order = \App\Modules\Liquidity\Models\Order::find($trn->crypto_details_id);

            if (Common::isModuleAvailable('FeeEngine')) {
                $feeCharges = \App\Modules\FeeEngine\Models\FeeTransactionCharge::with(['group', 'currency'])
                    ->where(function ($q) use ($b2c2Order, $trn) {
                        if ($b2c2Order) {
                            $q->orWhere('b2c2_order_id', $b2c2Order->id);
                        }
                        if ($trn->tr_request_id) {
                            $q->orWhere('tr_request_id', $trn->tr_request_id);
                        }
                    })
                    ->orderBy('id')
                    ->get();
            }
        }

        $fireblocksMonitoring = $this->buildFireblocksMonitoringContext($trn, $transactionRequest);

        $fireblocksCryptoBeneficiary = null;
        if ($transactionRequest && strcasecmp((string) $transactionRequest->transaction_nature, 'Withdraw') === 0) {
            $fireblocksCryptoBeneficiary = TrFireblocksBeneficiaryResolver::resolve($transactionRequest);
        }

        return view("crypto-management.crypto-transactions.edit." . $needleMapping[$trn->transaction_nature], compact('trn', 'transactionRequest', 'changes', 'accounts', 'docs', 'transaction_log', 'b2c2Order', 'feeCharges', 'fireblocksMonitoring', 'fireblocksCryptoBeneficiary'));
    }

    protected function buildFireblocksMonitoringContext(AccountTransaction $trn, ?TRRequest $transactionRequest = null): ?array
    {
        return app(\App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringTransactionDetailService::class)
            ->buildForLedger($trn, ['tr_request' => $transactionRequest]);
    }

    protected function buildFireblocksMonitoringContextForRequest(TRRequest $transactionRequest): ?array
    {
        $ledgerRow = AccountTransaction::query()
            ->where('tr_request_id', $transactionRequest->id)
            ->orderByDesc('id')
            ->first();

        return app(\App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringTransactionDetailService::class)
            ->buildForLedger($ledgerRow, ['tr_request' => $transactionRequest]);
    }

    public function updateCryptoTransaction(Request $request)
    {
        $this->validate($request, [
            'supporting_documents' => [new SupportingDocumentsRule]
        ]);

        $trn = AccountTransaction::find($request->get("id"));
        $client = ClientHelper::getClientByAccountId($request->get("account_id"));

        if ('Approved' != $trn->status) {
            $this->validate($request, [
                'account_id' => 'required',
                'transaction_date' => 'required',
                'transaction_time' => 'required',
                'transaction_type' => 'required',
                'remarks' => 'required'
            ]);

            $to_wallet_address = null;

            if ($request->get("transaction_nature") == 'Withdraw') {
                $this->validate($request, [
                    'to_wallet_address' => 'required'
                ]);

                $to_wallet_address = $request->get("to_wallet_address");
            }

            $account = Account::find($request->get("account_id"));
            $currency = Currency::where('id', $account->account_currency)->first();

            // if account is not available alert it
            if (!$account) {
                return redirect("/crypto-management/crypto-transactions")->with('error', "Account details not found.");
            }

            if ($currency) {
                if ($currency->is_crypto) {
                    $this->validate($request, [
                        'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
                    ]);
                } else {
                    $this->validate($request, [
                        'amount' => 'required|numeric|min:1',
                    ]);
                }
            } else {
                return redirect(url()->previous())->with('error', "Currency not found");
            }
            // validate time
            $time = Common::validateTime($request->get("transaction_time"));
            if (!$time) {
                return redirect(url()->previous())->with('error', "Please provide a valid time in 24 hours format.");
            }

            // restrict future date
            if (!self::isBelowToday($request->get("transaction_date"))) {
                return redirect("/crypto-management/crypto-transactions")->with('error', "Please provide past or current date.");
            }

            // // check if debit account is having enough balance
            $balance = Common::getAccountBalanceById($account->id);

            // create client transaction

            if ($request->get("amount") > $balance && $trn->amount != $request->get("amount")) {
                return redirect(url()->previous())->with('error', "You don't have enough money in your account");
            }

            if ($request->get("transaction_type") == "Debit" && $request->get("transaction_nature") != 'Withdraw') {
                $linked_trn = AccountTransaction::find($trn->link_tr_id);

                $linked_trn->link_tr_id = $trn->id;
                $linked_trn->save();
            }

            $trn->account_id = $request->get("account_id");
            $trn->currency_id = $account->account_currency;
            $trn->transaction_type = $request->get("transaction_type");
            $trn->transaction_nature = $request->get("transaction_nature");
            $trn->transaction_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"))->format("Y-m-d") . " " . $time;
            $trn->amount = $request->get("amount");
            $trn->to_wallet_address = $to_wallet_address;
            $trn->remarks = $request->get("remarks");
            $trn->updated_by = Auth::user()->id;

            $trn->save();
        }

        Common::uploadSupportingDocuments($request, $trn->requestedTrn ? $trn->requestedTrn : $trn, $client);

        if (!Common::canUserMakeChange()) {
            return redirect("crypto-management/crypto-transactions/edit/crypto-transaction/" . $trn->id)->with('success', "Changes has been submitted to your line manager for approval.");
        } else {
            return redirect("/crypto-management/crypto-transactions")->with('success', "Crypto " . $request->get("transaction_nature") . " details updated successfully.");
        }
    }

    public function updateDepositCryptoTransaction(Request $request)
    {
        $this->validate($request, [
            'supporting_documents' => [new SupportingDocumentsRule]
        ]);

        $client = ClientHelper::getClientByAccountId($request->get("credit_account_id"));
        $trn = AccountTransaction::find($request->get("id"));

        if ('Approved' != $trn->status) {
            $this->validate($request, [
                'credit_account_id' => 'required',
                'transaction_date' => 'required',
                'transaction_time' => 'required',
                'transaction_type' => 'required',
                'remarks' => 'required'
            ]);

            $account = Account::find($request->get("credit_account_id"));
            $currency = Currency::where('id', $account->account_currency)->first();

            // if account is not available alert it
            if (!$account) {
                return redirect("/crypto-management/crypto-transactions")->with('error', "Account details not found.");
            }
            if ($currency) {
                if (!$trn->is_fee_transaction) {
                    $this->validate($request, [
                        'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
                    ]);
                } else {
                    $this->validate($request, [
                        'amount' => 'required|numeric|min:1',
                    ]);
                }
            } else {
                return redirect(url()->previous())->with('error', "Currency not found");
            }

            // validate time
            $time = Common::validateTime($request->get("transaction_time"));
            if (!$time) {
                return redirect("/finance-management/transactions/new/internal")->with('error', "Please provide a valid time in 24 hours format.");
            }

            // create client transaction

            $trn->account_id = $request->get("credit_account_id");
            $trn->currency_id = $account->account_currency;
            $trn->transaction_type = $request->get("transaction_type");
            $trn->transaction_nature = $request->get("transaction_nature");
            $trn->transaction_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"))->format("Y-m-d") . " " . $time;
            $trn->amount = $request->get("amount");
            $trn->remarks = $request->get("remarks");
            $trn->updated_by = Auth::user()->id;

            $trn->save();
        }

        Common::uploadSupportingDocuments($request, $trn->requestedTrn ? $trn->requestedTrn : $trn, $client);

        if (!Common::canUserMakeChange()) {
            return redirect("crypto-management/crypto-transactions/edit/crypto-transaction/" . $trn->id)->with('success', "Changes has been submitted to your line manager for approval.");
        } else {
            return redirect("/crypto-management/crypto-transactions")->with('success', "Crypto " . $request->get("transaction_nature") . " details updated successfully.");
        }
    }

    public function getPendingTransactionRequests(Request $request)
    {
        $pendingStatuses = ['Pending', 'Initiated', 'Initial Review', 'Pending OTP', 'Needs Review'];
        $modeOptions = ['Buy', 'Sell', 'Deposit', 'Withdraw', 'Swap'];

        if ($request->ajax()) {
            return $this->cryptoRequestGridDataTable(
                $request,
                $pendingStatuses,
                'Pending',
                '/crypto-management/crypto-pending-transactions/view/crypto-transaction/'
            );
        }

        return view('crypto-management.crypto-transactions.list-pending-transactions', [
            'modeOptions' => $modeOptions,
            'statusOptions' => $pendingStatuses,
            'portalOptions' => $this->cryptoRequestPortalOptions($pendingStatuses),
            'defaultStatus' => 'Pending',
            'statusAllLabel' => 'All in progress',
        ]);
    }

    public function getRejectedTransactionRequests(Request $request)
    {
        $rejectedStatuses = ['Rejected', 'Cancelled'];
        $modeOptions = ['Buy', 'Sell', 'Deposit', 'Withdraw', 'Swap'];

        if ($request->ajax()) {
            return $this->cryptoRequestGridDataTable(
                $request,
                $rejectedStatuses,
                'Rejected',
                '/crypto-management/crypto-rejected-transactions/view/crypto-transaction/'
            );
        }

        return view('crypto-management.crypto-transactions.list-rejected-transactions', [
            'modeOptions' => $modeOptions,
            'statusOptions' => $rejectedStatuses,
            'portalOptions' => $this->cryptoRequestPortalOptions($rejectedStatuses),
            'defaultStatus' => 'Rejected',
            'statusAllLabel' => 'All rejected',
        ]);
    }

    /**
     * @param  array<int, string>  $statusOptions
     */
    protected function cryptoRequestPortalOptions(array $statusOptions)
    {
        return TRRequest::query()
            ->where('crypto_amount', '>', 0)
            ->whereIn('status', $statusOptions)
            ->whereNotNull('crypto_system')
            ->where('crypto_system', '!=', '')
            ->distinct()
            ->orderBy('crypto_system')
            ->pluck('crypto_system')
            ->filter()
            ->values();
    }

    /**
     * @param  array<int, string>  $statusOptions
     */
    protected function cryptoRequestGridDataTable(Request $request, array $statusOptions, string $defaultStatus, string $viewBasePath)
    {
        $query = TRRequest::query()
            ->from('account_transaction_requests')
            ->select([
                'account_transaction_requests.id',
                'account_transaction_requests.transaction_nature',
                'account_transaction_requests.crypto_amount',
                'account_transaction_requests.crypto_system',
                'account_transaction_requests.transaction_date',
                'account_transaction_requests.status',
                'account_transaction_requests.to_wallet_address',
                'account_transaction_requests.reject_reason',
                'debit_account.title as debit_title',
                'debit_account.account_number as debit_account_number',
                'receiver_account.title as receiver_title',
                'receiver_account.account_number as receiver_account_number',
                'creator_user.name as creator_name',
            ])
            ->leftJoin('accounts as debit_account', 'debit_account.id', '=', 'account_transaction_requests.account_id')
            ->leftJoin('accounts as receiver_account', 'receiver_account.account_number', '=', 'account_transaction_requests.receiver_account_number')
            ->leftJoin('users as creator_user', 'creator_user.id', '=', 'account_transaction_requests.created_by')
            ->where('account_transaction_requests.crypto_amount', '>', 0)
            ->where(function ($q) {
                $q->where('account_transaction_requests.is_deleted', false)
                    ->orWhereNull('account_transaction_requests.is_deleted');
            });

        $status = trim((string) $request->input('filter_status', $defaultStatus));
        if ($status !== '' && strtolower($status) !== 'all') {
            $query->where('account_transaction_requests.status', $status);
        } else {
            $query->whereIn('account_transaction_requests.status', $statusOptions);
        }

        $mode = trim((string) $request->input('filter_mode', ''));
        if ($mode !== '' && strtolower($mode) !== 'all') {
            $query->where('account_transaction_requests.transaction_nature', $mode);
        }

        $portal = trim((string) $request->input('filter_portal', ''));
        if ($portal !== '' && strtolower($portal) !== 'all') {
            $query->where('account_transaction_requests.crypto_system', $portal);
        }

        $dateFrom = trim((string) $request->input('filter_date_from', ''));
        if ($dateFrom !== '') {
            $query->whereDate('account_transaction_requests.transaction_date', '>=', $dateFrom);
        }

        $dateTo = trim((string) $request->input('filter_date_to', ''));
        if ($dateTo !== '') {
            $query->whereDate('account_transaction_requests.transaction_date', '<=', $dateTo);
        }

        $dataTable = Datatables::of($query)
            ->filter(function ($q) use ($request) {
                $search = trim((string) $request->input('search.value'));
                if ($search === '') {
                    return;
                }

                $q->where(function ($inner) use ($search) {
                    $inner->where('account_transaction_requests.id', 'like', "%{$search}%")
                        ->orWhere('account_transaction_requests.transaction_nature', 'like', "%{$search}%")
                        ->orWhere('account_transaction_requests.crypto_system', 'like', "%{$search}%")
                        ->orWhere('account_transaction_requests.status', 'like', "%{$search}%")
                        ->orWhere('account_transaction_requests.to_wallet_address', 'like', "%{$search}%")
                        ->orWhere('account_transaction_requests.crypto_amount', 'like', "%{$search}%")
                        ->orWhere('debit_account.title', 'like', "%{$search}%")
                        ->orWhere('debit_account.account_number', 'like', "%{$search}%")
                        ->orWhere('receiver_account.title', 'like', "%{$search}%")
                        ->orWhere('receiver_account.account_number', 'like', "%{$search}%")
                        ->orWhere('creator_user.name', 'like', "%{$search}%");
                });
            }, true)
            ->addColumn('view', function ($row) use ($viewBasePath) {
                $url = url($viewBasePath . $row->id);

                return '<a href="' . e($url) . '"><i class="fal fa-tv"></i></a>';
            })
            ->addColumn('debit_account', function ($row) {
                if ($row->transaction_nature === 'Deposit') {
                    return '--';
                }

                $title = $row->debit_title ?: '--';
                if (!empty($row->debit_account_number)) {
                    $title .= '<br><small><i>' . e($row->debit_account_number) . '</i></small>';
                }

                return $title;
            })
            ->addColumn('credit_or_wallet', function ($row) {
                if (!empty($row->receiver_title)) {
                    $html = e($row->receiver_title);
                    if (!empty($row->receiver_account_number)) {
                        $html .= '<br><small><i>' . e($row->receiver_account_number) . '</i></small>';
                    }

                    return $html;
                }

                if ($row->transaction_nature === 'Deposit') {
                    $html = e($row->debit_title ?: '--');
                    if (!empty($row->debit_account_number)) {
                        $html .= '<br><small><i>' . e($row->debit_account_number) . '</i></small>';
                    }

                    return $html;
                }

                return e($row->to_wallet_address ?: '--');
            })
            ->addColumn('crypto_amount_fmt', function ($row) {
                return e(Common::formatNumber($row->crypto_amount, true));
            })
            ->addColumn('portal', function ($row) {
                $portal = $row->crypto_system
                    ? ucwords(str_replace('_', ' ', (string) $row->crypto_system))
                    : '';

                return $portal !== ''
                    ? '<span class="badge" style="text-transform: capitalize;">' . e($portal) . '</span>'
                    : '';
            })
            ->addColumn('request_date', function ($row) {
                if (empty($row->transaction_date)) {
                    return '—';
                }

                return Carbon::parse($row->transaction_date)->format('d M, Y');
            })
            ->addColumn('status_badge', function ($row) {
                $colors = [
                    'Pending' => 'badge-primary',
                    'Initiated' => 'badge-info',
                    'Initial Review' => 'badge-info',
                    'Pending OTP' => 'badge-warning',
                    'Needs Review' => 'badge-warning',
                    'Approved' => 'badge-success',
                    'Rejected' => 'badge-danger',
                    'Cancelled' => 'badge-secondary',
                ];
                $class = $colors[$row->status] ?? 'badge-secondary';
                $html = '<span class="badge ' . $class . '">' . e($row->status) . '</span>';
                if ($row->status === 'Rejected' && !empty($row->reject_reason)) {
                    $html .= ' <i class="fal fa-question-circle" title="' . e($row->reject_reason) . '"></i>';
                }

                return $html;
            })
            ->addColumn('actions', function ($row) {
                if ($row->status === 'Approved') {
                    return '';
                }

                return '<a href="javascript:deleteTransaction(' . (int) $row->id . ')"><i class="alert-danger fal fa-times-circle rounded-circle"></i></a>';
            })
            ->rawColumns(['view', 'debit_account', 'credit_or_wallet', 'portal', 'status_badge', 'actions']);

        $dataTable->orderColumn('account_transaction_requests.id', function ($query, $order) {
            $query->reorder()->orderBy('account_transaction_requests.id', $order);
        });
        $dataTable->orderColumn('account_transaction_requests.transaction_nature', function ($query, $order) {
            $query->reorder()->orderBy('account_transaction_requests.transaction_nature', $order);
        });
        $dataTable->orderColumn('account_transaction_requests.crypto_amount', function ($query, $order) {
            $query->reorder()->orderBy('account_transaction_requests.crypto_amount', $order);
        });
        $dataTable->orderColumn('account_transaction_requests.crypto_system', function ($query, $order) {
            $query->reorder()->orderBy('account_transaction_requests.crypto_system', $order);
        });
        $dataTable->orderColumn('account_transaction_requests.transaction_date', function ($query, $order) {
            $query->reorder()->orderBy('account_transaction_requests.transaction_date', $order)
                ->orderByDesc('account_transaction_requests.id');
        });
        $dataTable->orderColumn('account_transaction_requests.status', function ($query, $order) {
            $query->reorder()->orderBy('account_transaction_requests.status', $order);
        });
        $dataTable->orderColumn('creator_user.name', function ($query, $order) {
            $query->reorder()->orderBy('creator_user.name', $order);
        });

        return $dataTable->make(true);
    }

    public function viewCryptoTransactionRequest($id, $nid = null)
    {
        // dd('ABCD');
        $transaction = TRRequest::find($id);
        $transaction_log = [];
        
        if ($nid) {
            Common::readNotification($nid);
        }
        //dd($transaction);
        $sanctions = json_encode(array());

        if ($transaction->transaction_nature == 'Withdraw' && $transaction->to_wallet_address != '') {
            $bitpaceHelper = new BitpaceMerchantApis();
            //0x56D65710c4192bA56B1c8838db6B59493986A226 Clean Wallet
            //0x1da5821544e25c636c1417ba96ade4cf6d2f9b5a Sanctioned Wallet
            $sanctions  = $bitpaceHelper->getChainAnalysisInfo($transaction->to_wallet_address);

            //dd(($sanctions));
        }

        $aml = new AmlRuleController();

        $aml_results = $aml->applyRules($transaction->toArray(), 'Transactions');
        //dd($aml_results);
        $detailsChanges = self::getTransactionChangesDetails($transaction);
        $rulesText = '';
        $amlNoteTexts = collect();
        $neroDataTransactionId = 0;
        $portalTransactionsUrl = '';
        if (Common::isModuleEnabled('NeroData')) {
            $neroDataHelper = new NeroDataHelper();
            $rulesText = $neroDataHelper->getNeroDataLogText($transaction->id);

            // if($transaction->crypto_system == 'fireblocks') {
            //        $notesJson = DB::table('crypto_transactions_log')
            //         ->where(function ($q) use ($transaction) {
            //             $q->where('transaction_id', $transaction->id);
            //             if (!empty($transaction->crypto_transaction_id)) {
            //                 $q->orWhere('transaction_id', $transaction->crypto_transaction_id);
            //             }
            //         })
            //         ->orderByDesc('id')
            //         ->limit(1)
            //         ->value('details');

            // } else {
            
            //$transactionRequest = TRRequest::find($transaction->tr_request_id);
            $crypto_transaction_id = $transaction->crypto_transaction_id;

            if ($crypto_transaction_id != '') {
                $transaction_log = DB::table('crypto_transactions_log')->where('transaction_id', $crypto_transaction_id)->orderBy('id', 'DESC')->get();
            }
            //dd($transaction_log);

           
            $notesJson = DB::table('crypto_transactions_log')
                ->where('transaction_id', $transaction->id)
                ->orderByDesc('id')
                ->limit(1)
                ->value(DB::raw(
                    "COALESCE(JSON_EXTRACT(details, '$.payload.notes'), JSON_EXTRACT(details, '$.response.notes'))"
                ));
           // }
            $notesArray = json_decode($notesJson, true);

            if (is_string($notesArray)) {
                // handles cases where DB returns JSON as a quoted string
                $notesArray = json_decode($notesArray, true);
            }

            $amlNoteTexts = collect($notesArray ?: [])
                ->map(fn($n) => is_array($n) ? ($n['text'] ?? null) : $n)
                ->filter()
                ->values();
            $neroDataTransactionId = $neroDataHelper->getNeroDataTransactionIdByTrId($transaction->id);
            $portalTransactionsUrl = $neroDataHelper->getPortalTransactionsUrl();
        }
        $fireblocksCryptoBeneficiary = TrFireblocksBeneficiaryResolver::resolve($transaction);
        $fireblocksMonitoring = $this->buildFireblocksMonitoringContextForRequest($transaction);

        return view("crypto-management.crypto-transactions.view." . strtolower($transaction->transaction_nature) . "-pending-transaction", compact('transaction', 'sanctions', 'aml_results', 'detailsChanges', 'rulesText',  'amlNoteTexts', 'neroDataTransactionId', 'portalTransactionsUrl', 'transaction_log', 'fireblocksCryptoBeneficiary', 'fireblocksMonitoring'));
    }

    public function fireblocksBeneficiaryModal(int $id)
    {
        $beneficiary = CryptoBeneficiary::with(['account:id,title,account_number', 'network:id,code,name', 'currency:id,short,name', 'compliance'])
            ->findOrFail($id);

        $travelRulesEnabled = FireblocksConfigService::travelRulesEnabled();

        return response()->json([
            'success' => true,
            'title' => 'Fireblocks Beneficiary #' . $beneficiary->id,
            'html' => view('fireblocks::beneficiaries.partials.detail-modal-content', [
                'beneficiary' => $beneficiary,
                'travelRulesEnabled' => $travelRulesEnabled,
            ])->render(),
        ]);
    }

    public function saveCryptoTransactionChanges(Request $request)
    {
        $this->validate($request, [
            'amount' => 'numeric|min:1',
            'bank_fee' => 'numeric|min:1',
            'transaction_date' => 'required',
            'debit_remarks' => 'required',
            'credit_remarks' => 'required'
        ]);

        // dd($request->all());
        DB::beginTransaction();

        try {
            $req = TRRequest::find($request->id);
            // dd($req);

            if ($req) {
                if ($req->status == "Rejected") {
                    DB::rollBack();

                    return redirect(url()->previous())->with("error", "Something went wrong. Please try again later.");
                }

                $receiver_is_active = $req->receiver ? $req->receiver->is_active : true;

                // check if the receiver account is active
                if (!$receiver_is_active || !$req->account->is_active) {
                    DB::rollBack();

                    return redirect(url()->previous())->with("error", "Debit and Credit account needs to be active");
                }

                $amount = $request->get("amount") ? $request->get("amount") : $request->get("bank_fee");

                if ($req->transaction_nature != 'Deposit') {
                    // check if there is enough balance in the client account
                    $debit_amount_by_transaction_nature = ["Buy" => $amount, "Sell" => $req->crypto_amount];

                    $debitAmount = (float) $debit_amount_by_transaction_nature[$req->transaction_nature];
                    if (! Common::trApprovalHasSufficientBalance($req, $debitAmount)) {
                        DB::rollBack();

                        return redirect(url()->previous())->with("error", Common::trApprovalInsufficientBalanceMessage($req, $debitAmount));
                    }
                }

                if ($request->has('crypto_amount')) {
                    $req->crypto_amount = $request->get('crypto_amount');
                }

                // $req->save();

                // dd('Request Date: ' . $request->get('transaction_date'), 'TRRequest Date Formatted: ', Carbon::parse($req->transaction_date)->format('Y-m-d'), 'Request Date Formatted: ', Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d'));
                if (Carbon::parse($req->transaction_date)->format('Y-m-d') != Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d')) {
                    $req->transaction_date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d H:i:s');
                }

                $data = [
                    'trn_id' => $req->id,
                    "amount" => $amount,
                    "crypto_amount" => $req->crypto_amount,
                    "transaction_date" => $req->transaction_date,
                    "remarks" => isset($request['debit_remarks']) ? $request['debit_remarks'] : null,
                    // 'service_fee_client_account' => $request->get("account_id"),
                    // 'service_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($account->account_currency, 'is_service_fee_bank'),
                    // 'service_fee_currency_id' => $account->account_currency,
                    // 'service_fee' => 0,
                    // 'service_fee_remarks' => $request->get('remarks'),
                    // 'is_service_fee_trn_hidden_for_client' => null,
                    // 'trn_fee_client_account' => $request->get("account_id"),
                    // 'trn_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($account->account_currency, 'is_trn_fee_bank'),
                    // 'trn_fee_currency_id' => $account->account_currency,
                    // 'trn_fee' => 0,
                    // 'trn_fee_remarks' => $request->get('remarks'),
                    // 'is_trn_fee_trn_hidden_for_client' => null
                ];

                $reviewer = Auth::user()->id;
                Common::saveTransactionDetails($data, $req, $reviewer);

                DB::commit();

                // // notify client
                // EmailNotification::notifyClientForTransactionApproval($trn);

                return redirect(url()->previous())->with("success", "Crypto Buy transaction request approved successfully");
            } else {
                DB::rollBack();

                return redirect(url()->previous())->with("error", "Invalid Crypto Buy transaction request.");
            }
        } catch (\Exception $e) {
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            Log::error('------------------------------------------------');

            DB::rollBack();

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function approveCryptoTransactionRequest(Request $request)
    {
        $this->validate($request, [
            'amount' => 'required|numeric|min:1',
            'transaction_date' => 'required',
            'debit_remarks' => 'required',
            'credit_remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule]
        ]);

        $req = TRRequest::find($request->id);
        $client = ClientHelper::getClientByAccountId($req->account_id);
        // dd($req);
        try {

            if ($req) {
                Common::neroDataComplianceStatus($req->compliance_status);

                if ($req->status == "Rejected") {
                    return redirect(url()->previous())->with("error", "Something went wrong. Please try again later.");
                }

                $trn_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"));
                $credit_transaction_date = $trn_date->format("Y-m-d H:i:s");
                $debit_transaction_date = Carbon::parse($trn_date)->addSeconds(60)->format("Y-m-d H:i:s");

                if ($request->has('crypto_amount')) {
                    $req->crypto_amount = $request->get('crypto_amount');
                }

                $req->save();

                $receiver_account = $req->receiver;

                // check if the receiver account is active
                if (!$receiver_account->is_active || !$req->account->is_active) {
                    return redirect(url()->previous())->with("error", "Debit and Credit account needs to be active");
                }

                // check if there is enough balance in the client account
                $debit_amount_by_transaction_nature = ["Buy" => $request->get("amount"), "Sell" => $req->crypto_amount];
                $credit_amount_by_transaction_nature = ["Sell" => $request->get("amount"), "Buy" => $req->crypto_amount];

                $debitAmount = (float) $debit_amount_by_transaction_nature[$req->transaction_nature];
                if (! Common::trApprovalHasSufficientBalance($req, $debitAmount)) {
                    return redirect(url()->previous())->with("error", Common::trApprovalInsufficientBalanceMessage($req, $debitAmount));
                }
                //if Currency GED
                $status =  'Approved';
                $crypto_transaction_id = '';
                // if ($req->crypto_system == 'GED') {
                //     $status =  'Pending';
                //     if ($req->transaction_nature == 'Buy') {
                //         Log::info('Minting process: Started ');
                //         $mint = $this->mintBuyRequest($receiver_account, $req->to_wallet_address,  $request->get("credit_remarks"), $request->get("amount"));
                //         //print_r($mint); die();
                //         if ($mint->code == 200) {
                //             $response = $mint->response;
                //             $crypto_transaction_id = $response->mint_external_id;
                //             if ($response->mint_status == 'Queued')
                //                 $status =  'Pending';
                //             else
                //                 $status =  'Approved';
                //             $this->GEDhelper->storeApiResponseData($crypto_transaction_id, json_encode($response), 'GED');
                //         } else {
                //             // Log::info('Blockchain account creation: '  .$mint->message);
                //             return redirect(url()->previous())->with("error", $mint->message . 'Code: ' . $mint->code);
                //         }
                //     }

                //     if ($req->transaction_nature == 'Sell') {
                //         $crypto_transaction_id = $req->crypto_transaction_id;
                //         $debit_account = Account::find($req->account_id);

                //         $redeem = $this->GEDhelper->sendRedumptionUpdate($debit_account->account_number, $crypto_transaction_id, 'Created');
                //         if (!empty($redeem)) {
                //             if (!$redeem->success) {
                //                 return redirect(url()->previous())->with("error", "Transaction can not be approved, Redumption error");
                //             }
                //         }
                //     }
                // }

                // create debit transaction
                $trn = new AccountTransaction();

                $trn->account_id = $req->account_id;
                $trn->tr_request_id = $req->id;
                $trn->currency_id = $req->currency_id;
                $trn->transaction_type = "Debit";
                $trn->transaction_nature = $req->transaction_nature;
                $trn->transaction_date = $debit_transaction_date;
                $trn->amount = $debit_amount_by_transaction_nature[$req->transaction_nature];
                $trn->is_crypto = true;
                $trn->remarks = $request->get("debit_remarks");
                $trn->status = $status;
                $trn->created_by = $req->created_by;
                $trn->crypto_transaction_id = $crypto_transaction_id;
                $trn->crypto_system = $req->crypto_system;
                $trn->approved_by = Auth::user()->id;
                $trn->approved_at = Carbon::now();

                if ($trn->save()) {

                    // create credit transaction
                    $cr_trn = new AccountTransaction();
                    $cr_trn->account_id = $receiver_account->id;
                    $cr_trn->tr_request_id = $req->id;
                    $cr_trn->link_tr_id = $trn->id;
                    $cr_trn->currency_id = $receiver_account->account_currency;
                    $cr_trn->transaction_type = "Credit";
                    $cr_trn->transaction_nature = $req->transaction_nature;
                    $cr_trn->transaction_date = $credit_transaction_date;
                    $cr_trn->to_wallet_address =  $req->to_wallet_address;
                    $cr_trn->amount = $credit_amount_by_transaction_nature[$req->transaction_nature];
                    $cr_trn->is_crypto = true;
                    $cr_trn->remarks = $request->get("credit_remarks");
                    $cr_trn->status = $status;
                    $cr_trn->created_by = $req->created_by;
                    $cr_trn->crypto_transaction_id = $crypto_transaction_id;
                    $cr_trn->crypto_system = $req->crypto_system;
                    $cr_trn->approved_by = Auth::user()->id;
                    $cr_trn->approved_at = Carbon::now();
                    $cr_trn->save();
                }

                // update link transaction id in the debit transaction
                $trn->link_tr_id = $cr_trn->id;
                $trn->save();

                // approve transaction request
                $req->link_tr_id = $trn->id;
                $req->amount = $request->get("amount");
                $req->approved_by = Auth::user()->id;
                $req->status = $status;
                $req->save();

                Common::uploadSupportingDocuments($request, $req, $client);

                // notify client
                EmailNotification::notifyClientForTransactionApproval($trn);

                return redirect(url()->previous())->with("success", "Crypto Buy transaction request approved successfully");
            } else {
                return redirect(url()->previous())->with("error", "Invalid Crypto Buy transaction request.");
            }
        } catch (Exception $e) {
            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function mintBuyRequest($credit_account, $wallet_address, $memo, $amount)
    {
        //$api_auth_key =  $this->GEDhelper->getApiAuthKey($credit_account_number);
        $cents = 0;
        //convert amount to cents
        if ($amount > 0) {
            $cents = $amount * 100;
        }

        $ged_customer = $this->GEDhelper->customer($credit_account);
        if ($ged_customer->code == 400) {
            Log::info('Customer on boarding Admin: ' . $ged_customer->message);
            return $ged_customer;
        }

        if ($ged_customer->code == 200) {
            $blockchain_account = $this->GEDhelper->getBlockchainAccount($credit_account, $wallet_address, $memo);
            if ($blockchain_account->code == 200) {
                $mint = $this->GEDhelper->mint($credit_account->account_number, $cents);
                Log::info('Admin Minting: Minted');
                return $mint;
            } else {
                Log::info('Blockchain account creation: '  . $blockchain_account->message);
                return $blockchain_account;
            }
        }
    }


    public function approveDepositeCryptoTransactionRequest(Request $request)
    {
        $this->validate($request, [
            'transaction_date' => 'required',
            'bank_fee' => 'required|numeric|min:0',
            'debit_remarks' => 'required',
            'credit_remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule]
        ]);

        $req = TRRequest::find($request->id);
        $client = ClientHelper::getClientByAccountId($req->account_id);
        // dd($req);
        try {

            if ($req) {

                Common::neroDataComplianceStatus($req->compliance_status);

                if ($req->status == "Rejected") {
                    return redirect(url()->previous())->with("error", "Something went wrong. Please try again later.");
                }

                $req->transaction_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"))->format("Y-m-d H:i:s");
                $req->save();

                // check if the receiver account is active
                if (!$req->account->is_active) {
                    return redirect(url()->previous())->with("error", "Credit account needs to be active");
                }

                // create debit transaction
                $credit_trn = new AccountTransaction();

                $credit_trn->account_id = $req->account_id;
                $credit_trn->tr_request_id = $req->id;
                $credit_trn->currency_id = $req->currency_id;
                $credit_trn->transaction_type = "Credit";
                $credit_trn->transaction_nature = $req->transaction_nature;
                $credit_trn->transaction_date = $req->transaction_date;
                $credit_trn->amount = $req->crypto_amount;
                $credit_trn->is_crypto = true;
                $credit_trn->remarks = $request->get("credit_remarks");
                $credit_trn->status = "Approved";
                $credit_trn->created_by = $req->created_by;
                $credit_trn->crypto_system = $req->crypto_system;
                $credit_trn->approved_by = Auth::user()->id;
                $credit_trn->approved_at = Carbon::now();

                $credit_trn->save();

                // approve transaction request
                $req->approved_by = Auth::user()->id;
                $req->status = "Approved";
                $req->save();

                if ($request->get("bank_fee") > 0) {
                    $debit_trn = new AccountTransaction();

                    $debit_trn->account_id = $req->account_id;
                    $debit_trn->tr_request_id = $req->id;
                    $debit_trn->currency_id = $req->currency_id;
                    $debit_trn->transaction_type = "Debit";
                    $debit_trn->transaction_nature = $req->transaction_nature;
                    $debit_trn->transaction_date = Carbon::parse($credit_trn->transaction_date)->addSeconds(60)->format("Y-m-d H:i:s");
                    $debit_trn->amount = $request->get("bank_fee");
                    $debit_trn->is_fee_transaction = true;
                    $debit_trn->is_crypto = true;
                    $debit_trn->remarks = $request->get("debit_remarks");
                    $debit_trn->status = "Approved";
                    $debit_trn->created_by = $req->created_by;
                    $debit_trn->crypto_system = $req->crypto_system;
                    $debit_trn->approved_by = Auth::user()->id;
                    $debit_trn->approved_at = Carbon::now();

                    $debit_trn->link_tr_id = $credit_trn->id;

                    $debit_trn->save();

                    $credit_trn->link_tr_id = $debit_trn->id;
                    $credit_trn->save();
                }

                Common::uploadSupportingDocuments($request, $req, $client);

                // notify client
                EmailNotification::notifyClientForTransactionApproval($credit_trn);

                return redirect(url()->previous())->with("success", "Crypto Deposit transaction request approved successfully");
            } else {
                return redirect(url()->previous())->with("error", "Invalid Crypto Deposit transaction request.");
            }
        } catch (Exception $e) {
            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }
    public function approveWithdrawOtp()
    {

        //dd(session('transaction_2fa'));
        if (session('transaction_2fa') == true) {
            $form_request = session('form_request');

            //$request = new Request($request);
            $request = new Request(
                $form_request, // Request data (POST body)
                [], // Query parameters (GET)
                [], // Attributes
                [], // Cookies
                [], // Files
                [], // Server variables
                'POST' // HTTP Method
            );
            //$request = Request::createFrom(request(), [], $form_request);
            //$request->setMethod('POST');
            //dd($request);
            return $this->approveWithdrawCryptoTransactionRequest($request);
        }
        //dd($request);

    }

    public function approveWithdrawCryptoTransactionRequest(Request $request)
    {
        //dd($request->all());
        // session(['form_request' => '']);

        // dd($request);
        $this->validate($request, [
            'transaction_date' => 'required',
            'crypto_amount' => 'required:double',
            'remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule]
        ]);

        $req = TRRequest::find($request->id);
        //dd($req);
        $client = ClientHelper::getClientByAccountId($req->account_id);

        try {

            if ($req) {

                Common::neroDataComplianceStatus($req->compliance_status);

                if ($req->status == "Rejected" || $req->status == 'Approved') {
                    session()->forget('form_submitted');
                    return redirect("/crypto-management/crypto-pending-transactions/view/crypto-transaction/" . $req->id)->with("error", "Something went wrong. Please try again later.");
                }

                $req->transaction_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"))->addSeconds(10)->format("Y-m-d H:i:s");
                $req->save();

                // check if the receiver account is active
                if (!$req->account->is_active) {
                    session()->forget('form_submitted');
                    return redirect("/crypto-management/crypto-pending-transactions/view/crypto-transaction/" . $req->id)->with("error", "Debit account needs to be active");
                }

                // check if there is enough balance in the client account
                if (! Common::trApprovalHasSufficientBalance($req, (float) $request->get('crypto_amount'))) {
                    session()->forget('form_submitted');
                    return redirect("/crypto-management/crypto-pending-transactions/view/crypto-transaction/" . $req->id)->with("error", Common::trApprovalInsufficientBalanceMessage($req, (float) $request->get('crypto_amount')));
                }

                //Send API call if its from crypto_system is present
                $crypto_order_id = '';
                $destination_tag = $req->destination_tag ?? '';
                $tr_status = "Approved";

                if ($req->crypto_system != '' && $req->crypto_system == 'fireblocks') {
                    $withdrawalService = app(WithdrawalService::class);
                    
                    // TODO: Replace with your actual Fireblocks source vault account ID
                    
                    $sourceVaultAccountId = '0'; // <-- HARDCODED: Set your source vault account ID here
                   
                    $assetid = '';
                    $network_code = $req->crypto_network;
                    $network = Network::where('code', $network_code)->where('currency_id',$req->currency_id)->first();
                    //dd($network);
                    if(!empty($network) && !empty($network->assets)) {
                        $asset = Asset::where('network_id',$network->id)->where('currency_id',$req->currency_id)->first();
                        $assetId = $asset->asset_id; 
                        //dd($asset);
                    }
                    if (empty($assetId)) {
                        session()->forget('form_submitted');
                        return redirect("/crypto-management/crypto-pending-transactions/view/crypto-transaction/" . $req->id)->with('error', "Network not specified for Fireblocks withdrawal.");
                    }
                    //$asset = Asset::where('asset_id', $assetId)->first();
                    //dd($asset  );
                    if(empty($asset)) {
                        session()->forget('form_submitted');
                        return redirect("/crypto-management/crypto-pending-transactions/view/crypto-transaction/" . $req->id)->with('error', "Unsupported asset for Fireblocks withdrawal.");
                    }  
                    //echo $asset->vault_account_id; die();
                   if ($asset->vault_account_id === null && $asset->vault_account_id === '') {
                        session()->forget('form_submitted');
                        return redirect("/crypto-management/crypto-pending-transactions/view/crypto-transaction/" . $req->id)->with('error', "Vault account not configured for the asset.");
                    }
                    //dd($asset);
                    // $validate =  $withdrawalService->validateAddress($assetId, $req->to_wallet_address, $destination_tag);
                    // //dd($validate);
                    // if (!$validate['success']) {
                    //     $errorMsg = $validate['error'] ?? 'Unknown error';
                    //     session()->forget('form_submitted');
                    //     return redirect("/crypto-management/crypto-pending-transactions/view/crypto-transaction/" . $req->id)->with('error', "Fireblocks address validation failed: " . $errorMsg);
                    // }

                    $sourceVaultAccountId = $asset->vault_account_id;

                    $beneficiary = null;
                    if (!empty($req->crypto_details_id)) {
                        $beneficiary = \App\Modules\Fireblocks\Models\CryptoBeneficiary::with('externalWallet')
                            ->find($req->crypto_details_id);
                    }
                    if (!$beneficiary && !empty($req->to_wallet_address)) {
                        $beneficiary = \App\Modules\Fireblocks\Models\CryptoBeneficiary::with('externalWallet')
                            ->where('is_active', true)
                            ->where(function ($q) use ($req) {
                                $q->where('wallet_address', $req->to_wallet_address)
                                    ->orWhereRaw('LOWER(wallet_address) = ?', [strtolower($req->to_wallet_address)]);
                            })
                            ->orderByDesc('id')
                            ->first();
                    }

                    $fireblocksWalletId = $beneficiary?->fireblocks_wallet_id
                        ?: $beneficiary?->externalWallet?->fireblocks_wallet_id
                        ?: $withdrawalService->resolveExternalWalletId($req->to_wallet_address, $assetId);

                    if (empty($fireblocksWalletId)) {
                        session()->forget('form_submitted');
                        return redirect("/crypto-management/crypto-pending-transactions/view/crypto-transaction/" . $req->id)
                            ->with('error', 'No Fireblocks EXTERNAL_WALLET found for destination. Address must be whitelisted before withdrawing.');
                    }
                   
                   // dd($validate);
                    $withdrawalData = [
                        'asset_id' => $assetId,
                        'source_vault_account_id' => $sourceVaultAccountId,
                        'destination_address' => $req->to_wallet_address,
                        'fireblocks_wallet_id' => $fireblocksWalletId,
                        'fireblocks_asset_status' => $beneficiary->fireblocks_asset_status ?? null,
                        'amount' => $request->get("crypto_amount"),
                        'destination_tag' => $req->destination_tag,
                        'note' => $request->get("remarks"),
                        'external_tx_id' => (string) $req->id,
                        'customer_ref_id' => (string) $req->account->client_id,
                        'failure_alert_context' => [
                            'account_id' => $req->account_id,
                            'channel' => 'Crypto transaction approval',
                        ],
                    ];
                   
                   // echo json_encode($withdrawalData);
                   // dd($withdrawalData);
                    $result = $withdrawalService->withdraw($withdrawalData);
                    
                    if (!$result['success']) {
                       // dd($result);
                        $errorMsg = $result['error'] ?? 'Unknown error';
                        session()->forget('form_submitted');
                        return redirect("/crypto-management/crypto-pending-transactions/view/crypto-transaction/" . $req->id)->with('error', "Fireblocks withdrawal failed: " . $errorMsg);
                    }
                    
                    

                    $crypto_order_id = $result['data']['fireblocks_tx_id'];
                    $fbStatus = $result['data']['status'] ?? 'SUBMITTED';
                    $fbSubStatus = $result['data']['sub_status'] ?? null;
                    CryptoLogHelper::logCryptoTransaction(
                        $crypto_order_id,
                        $details = ($result),
                        $cryptoSystem = 'Fireblocks',
                        $source = 'system'
                    );
                    $statusResolution = FireblocksStatusHelper::resolve($fbStatus, $fbSubStatus);
                    $tr_status = $statusResolution['internal_status'];
                    $order_data = [
                        'tr_request_id'    => $req->id,
                        'fireblocks_tx_id' => $crypto_order_id,
                        'primary_status'   => $statusResolution['primary_status'],
                        'sub_status'       => $statusResolution['sub_status'],
                        'internal_status'  => $statusResolution['internal_status'],
                        'message'          => $statusResolution['message'],
                    ];

                    Log::info('Fireblocks withdrawal status resolved', );

                    CryptoLogHelper::logCryptoTransaction(
                        $crypto_order_id,
                        $details = ($order_data),
                        $cryptoSystem = 'Fireblocks',
                        $source = 'system'
                    );
                }
                
                if ($req->crypto_system != '' && $req->crypto_system == 'Bitpace') {
                    //Session(['transaction_2fa' => 'false']);
                    // dd(session()->all());
                    //Session()->save();
                    //dd(Session::has('transaction_2fa'));

                        /**
                         * The following condition states
                         * If the user has not yet passed 2FA, redirect them to the 2FA verification page
                         *
                         * But if the admin user has already logged in, then it will not ask redirect to 2FA page
                         * and will redirect to Dashboard without thrwing any error
                         *
                         * Hence commented the code
                         */

                        /*if (session('transaction_2fa') === 'false') {
                        // User has not passed 2FA, redirect to the 2FA verification page
                        session(['transaction_req_id' => $request->id]);
                        //session(['form_request' => $request]);
                        session(['form_request' => $request->all()]);
                        return redirect('/user/2fa');
                    }*/
                        //dd(session('transaction_2fa'));

                        // $account = DB::table('accounts')
                        //     ->select(
                        //         'clients.id as client_id',
                        //         'users.id as user_id',
                        //         'crypto_addresses.crypto_address',
                        //         'crypto_addresses.destination_tag',
                        //         'clients.name',
                        //         'accounts.account_number',
                        //         'users.email',
                        //         'currencies.id as currency_id',
                        //         'currencies.short as currency',
                        //         'currencies.network'
                        //     )
                        //     ->leftJoin('clients', 'accounts.client_id', '=', 'clients.id')
                        //     ->leftJoin('client_users', 'client_users.client_id', '=', 'clients.id')
                        //     ->leftJoin('users', 'client_users.user_id', '=', 'users.id')
                        //     ->leftJoin('crypto_addresses', 'accounts.id', '=', 'crypto_addresses.account_id')
                        //     ->leftJoin('currencies', 'currencies.id', '=', 'accounts.account_currency')
                        //     ->where('accounts.id', '=', $req->account_id)
                        //     ->first();

                        // $name = $account->name;
                        // $email = $account->email;
                        // $currency_short = $account->currency;
                        // //$dt_address = $account->dt_address;
                        // $destination_tag = $req->destination_tag;
                        // $nameArr =  explode(' ', $name, 2);
                        // $firstname = $name;
                        // $lastname = '';

                        // if (count($nameArr) > 0) {
                        //     $firstname = $nameArr[0];
                        //     if (!empty($nameArr[1]))
                        //         $lastname = $nameArr[1];
                        //     else
                        //         $lastname = '';
                        // }

                        // $destination_tag_str = '';
                        // $payload = [
                        //     "customer" => [
                        //         "reference_id" => $req->account_id,
                        //         "first_name" => $firstname,
                        //         "last_name" => $lastname,
                        //         "email" => $email
                        //     ],
                        //     "withdraw" => [
                        //         "address" => $req->to_wallet_address

                        //     ],

                        //     "cryptocurrency" => $currency_short,
                        //     "cryptocurrency_amount" => $request->get("crypto_amount"),
                        //     "description" => $request->get("remarks")
                        // ];

                        // if (!empty($req->crypto_network)) {
                        //     $payload['network'] = $req->crypto_network;
                        // }

                        // // Add destination_tag only if it's not empty
                        // if (!empty($destination_tag)) {
                        //     $payload["withdraw"]["destination_tag"] = $destination_tag;
                        // }
                        //dd($payload);

                        //bitpace withdraw
                        /*
                    Commented the code to stop api call
                    $bitpaceHelper = new BitpaceMerchantApis();
                    $data = $bitpaceHelper->withdrawCoin($payload);
                    if (!empty($data)) {
                        if (!empty($data->order_id)) {
                            $crypto_order_id = $data->order_id;
                            $crypto_order_data = $bitpaceHelper->getOrderV2($crypto_order_id);
                            if ($crypto_order_data->status == 'PENDING') {
                                $tr_status = 'Pending';
                            }

                            $bitpaceHelper->storeApiResponseData($crypto_order_id, json_encode($crypto_order_data), $req->crypto_system);
                        }
                    }

                    //print_r($data);
                    if ($crypto_order_id == '') {
                        $message = '';
                        if (!empty($data->message))
                            $message = $data->message;
                        session(['transaction_req_id' => '']);
                        session(['form_request' => '']);
                        session()->forget('form_request');
                        session()->forget('transaction_req_id');
                        session(['transaction_2fa' => 'false']);
                        session()->forget('form_submitted');
                        return redirect("/crypto-management/crypto-pending-transactions/view/crypto-transaction/" . $req->id)->with('error', "Failed to post transaction on Crypto System. " . $message);
                    } */
                }

                // create debit transaction
                $trn = new AccountTransaction();
                $trn->account_id = $req->account_id;
                $trn->tr_request_id = $req->id;
                $trn->currency_id = $req->currency_id;
                $trn->transaction_type = "Debit";
                $trn->transaction_nature = $req->transaction_nature;
                $trn->transaction_date = $req->transaction_date;
                $trn->amount = $request->get("crypto_amount");
                $trn->to_wallet_address = $req->to_wallet_address;

                $trn->destination_tag = $destination_tag;
                $trn->is_crypto = true;
                $trn->crypto_transaction_id = $crypto_order_id;
                $trn->crypto_system = $req->crypto_system;
                $trn->remarks = $request->get("remarks");
                $trn->status = $tr_status;
                $trn->created_by = $req->created_by;
                $trn->crypto_system = $req->crypto_system;
                $trn->approved_by = Auth::user()->id;
                $trn->approved_at = Carbon::now();
                $trn->crypto_network = $req->crypto_network;
                //dd($trn);
                if ($trn->save() && $req->crypto_system == 'Bitpace') {


                    session(['transaction_req_id' => '']);
                    session(['form_request' => '']);
                    session()->forget('form_request');
                    session()->forget('transaction_req_id');
                    session(['transaction_2fa' => 'false']);
                }

                // approve transaction request
                $req->status = $tr_status;
                $req->crypto_transaction_id = $crypto_order_id;
                $req->save();

                //dd($trn);
                Common::uploadSupportingDocuments($request, $req, $client);

                // notify client

                $message_type = "success";
                $message =  "Crypto withdraw transaction request approved successfully";
                if ($tr_status == 'Pending') {
                    $message_type = "info";
                    $message =  "Crypto withdraw transaction approval request submitted and pending, please refresh the screen to check the latest status after some time.";
                } else {
                    EmailNotification::notifyClientForTransactionApproval($trn);
                }
                session()->forget('form_submitted');
                return redirect('/crypto-management/crypto-transactions')->with($message_type, $message);
            } else {
                return redirect('/crypto-management/crypto-transactions')->with("error", "Invalid Crypto withdraw transaction request.");
            }
        } catch (Exception $e) {
            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }



    public function rejectCryptoTransaction(Request $request)
    {
        $req = TRRequest::find($request->id);

        if ($req->status == "Approved") {
            return redirect(url()->previous())->with("error", "Something went wrong. Please try again later.");
        }

        if ($req) {
            $req->status = "Rejected";
            $req->rejected_by = Auth::user()->id;
            $req->rejected_at = Carbon::now();
            $req->reject_reason = $request->get("reject_reason");

            $req->save();
            
            $account_trn = $req->transactions()->first();
            
            if ($account_trn) {
                $account_trn->reject_reason = $request->get("reject_reason");
                $account_trn->rejected_by = Auth::user()->id;
                $account_trn->rejected_at = Carbon::now();
                
                $account_trn->save();
            }

            if ($req->transactions->count() > 0) {
                Common::rejectLinkedTransactions($req->transactions, $request->get('reject_reason'));
            }

            // notify client
            EmailNotification::notifyClientForTransactionRejection($req);

            return redirect("/crypto-management/crypto-pending-transactions")->with('success', "Transaction Rejected successfully.");
        } else {
            return redirect("/crypto-management/crypto-pending-transactions")->with('error', "Transaction not found.");
        }
    }

    public function deleteCryptoPendingTransaction(Request $request)
    {
        $trn = TRRequest::find($request->get("id"));

        if ($trn) {

            // delete documents if any
            if ($trn->docs && $trn->docs->count() > 0) {
                foreach ($trn->docs as $doc) {
                    S3Helper::fileDelete($doc->url);
                    $doc->delete();
                }
            }

            // delete transaction
            $trn->delete();
            return back()->with("success", "Transaction Request deleted successfully");
        } else {
            return back()->with("error", "Transaction Request not found");
        }
    }

    public function deleteCryptoApprovedTransaction(Request $request)
    {
        $trn = AccountTransaction::find($request->get("trn_id"));

        if ($trn) {
            // delete transaction
            $trn->delete();

            // delete if Transaction has any linked transaction.
            if ($trn->link_tr_id) {
                $linkedTrn = AccountTransaction::find($trn->link_tr_id);

                if ($linkedTrn) {
                    $linkedTrn->delete();
                }
            }

            return back()->with("success", "Transaction Request deleted successfully");
        } else {
            return back()->with("error", "Transaction Request not found");
        }
    }

    public function printTransaction($id)
    {
        $transaction = AccountTransaction::find($id);

        $trnRequest = TRRequest::find($transaction->tr_request_id);

        if ($trnRequest) {
            $docs = $trnRequest->docs;
        } else {
            $docs = [];
        }

        $view = view("pdfs.finance.crypto.approved." . strtolower($transaction->transaction_nature), compact('transaction', 'docs'));
        $pdf = PDF::loadHTML($view)->setPaper('a4');
        $file_name = strtolower($transaction->transaction_nature) . "-transaction-request.pdf";
        return $pdf->stream($file_name);
    }

    public function printTransactionRequest($id)
    {
        $transaction = TRRequest::find($id);

        $view = view("pdfs.finance.crypto.pending.request", compact('transaction'));
        $pdf = PDF::loadHTML($view)->setPaper('a4');
        $file_name = $transaction->transaction_nature . "-transaction-request.pdf";
        return $pdf->stream($file_name);
    }

    private static function getTransactionChangesDetails($transactionRequest)
    {
        $detailsChanges = null;
        if (
            !empty($transactionRequest->transactionDetails)
            // && !Auth::user()->hasRole('finance')
        ) {
            $transactionDetailsChanges = self::getChanges($transactionRequest->transactionDetails);

            if (count($transactionDetailsChanges) > 0) {
                $detailsChanges['maker'] = $transactionDetailsChanges[0][0]->maker->name;

                foreach ($transactionDetailsChanges[0] as $change) {
                    // dd($change);
                    $detailsChanges[$change->details[0]->key]['old_value'][] = $change->details[0]->old_value;
                    $detailsChanges[$change->details[0]->key]['new_value'][] = $change->details[0]->new_value;
                    $oldValue = $detailsChanges[$change->details[0]->key]['old_value'][count($detailsChanges[$change->details[0]->key]['old_value']) - 1];

                    if (strpos($change->details[0]->key, 'fee_client_account_id')) {
                        $oldValue = Account::find($detailsChanges[$change->details[0]->key]['old_value'][count($detailsChanges[$change->details[0]->key]['old_value']) - 1])->title;
                    }

                    $detailsChanges[$change->details[0]->key]['tooltip'] = '
                    <div class="tooltip-container">
                        <div class="tooltip-circle"
                            data-tooltip="' . e('Old Value: ' . $oldValue) . '">
                        </div>
                    </div>
                ';
                }

                // dd('Details', $detailsChanges);
            }
        }

        return $detailsChanges;
    }

    private static function getChanges($model)
    {
        $transaction = ChangeLogger::get($model);
        $cheque = ($model->cheque) ? ChangeLogger::get($model->cheque) : null;

        $changes = [];
        if ($transaction) {
            $changes[] = $transaction;
        }

        if ($cheque) {
            $changes[] = $cheque;
        }

        return $changes;
    }

    private function isBelowToday($date)
    {
        $today = Carbon::today();
        $ndate = Carbon::createFromFormat('d/m/Y h:m:s', $date . " 00:00:00");

        return $today->gte($ndate);
    }
}
