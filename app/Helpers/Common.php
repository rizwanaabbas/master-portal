<?php
# Abcd from CIB

/**
 * Created by PhpStorm.
 * User: aamiriqbal
 * Date: 31/03/2021
 * Time: 4:36 AM
 */

namespace App\Helpers;

use App\Helpers\FileFirewall;
use App\Helpers\S3Helper;
use App\Http\Controllers\LorumController;
use App\Jobs\SendWebhookNotification;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Bank;
use App\Models\BellNotification;
use App\Models\Client;
use App\Models\ClientAccount;
use App\Models\ClientCompanyFile;
use App\Models\ClientFileConfig;
use App\Models\ClientIndividualFile;
use App\Models\ClientKYC;
use App\Models\ClientUser;
use App\Models\Country;
use App\Models\Currency;
use App\Models\DropdownConfiguration;
use App\Models\IdempotencyKey;
use App\Models\MerchantWebhook;
use App\Models\ModelChange;
use App\Models\Module;
use App\Models\OpenPaydBankAccount;
use App\Models\OTPCode;
use App\Models\OwnCompany;
use App\Models\TransactionDetails;
use App\Models\TRRequest;
use App\Models\TRRequestDoc;
use App\Models\User;
use App\Models\UserCardLink;
use App\Models\TransactionMethod;
use App\Models\VirtualBankAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use Exception;

class Common
{

    public static function getFileNameFromURL($url)
    {
        $tmp = explode("/", $url);
        return $tmp[count($tmp) - 1];
    }

    public static function getLineManagerName($user)
    {
        if ($user && $user->manager_id > 0) {
            $manager = User::find($user->manager_id);

            if ($manager) {
                return $manager->name;
            } else {
                return "--";
            }
        } else {
            return "--";
        }
    }

    public static function fileUpload($file, $path)
    {
        try {
            $destinationPath = public_path($path);
            $newFileName = Str::uuid()->toString() . "." . $file->getClientOriginalExtension();
            $file->move($destinationPath, $newFileName);

            return url("/" . $path . "/" . $newFileName);
        } catch (exception $e) {
            var_dump($e);
            return 0;
        }
    }

    public static function fileDelete($url)
    {
        if ($url != "") {
            $tmp = explode("/", $url);
            unset($tmp[0]);
            unset($tmp[1]);
            unset($tmp[2]);
        }

        $path = public_path(implode("/", $tmp));

        if (file_exists($path)) {
            unlink($path);
        }
    }

    public static function fileUploadS3($file, $path)
    {
        $filename = Str::uuid()->toString() . "." . $file->getClientOriginalExtension();
        $path = $path . "/" . $filename;
        $s3 = Storage::disk('s3');
        $pathS3 = $s3->put($path, $file);
        
        return $s3->url($path);
    }


    public static function clientTypes()
    {
        return [
            'direct',
            'agency',
            'group'
        ];
    }

    public static function getSettings()
    {
        return GeneralSetting::first();
    }

    public static function successPercentageDD()
    {
        return ['25', '50', '75'];
    }

    public static function getManager()
    {
        return User::where('id', Auth::user()->manager_id)->first();
    }

    public static function getProposalStatusDD()
    {
        return ['Draft', 'Submitted', 'Validated', 'Rejected', 'Approved', 'Booked'];
    }

    public static function getProposalStatusManagerDD()
    {
        return ['Draft', 'Validated'];
    }

    public static function getEmployeesIds()
    {
        return User::where('manager_id', Auth::user()->id)->pluck('id')->toArray();
    }

    public static function getUserById($created_by)
    {
        return User::find($created_by);
    }

    public static function getUserNameById($user_id)
    {
        $user = User::find($user_id);
        return $user->name;
    }

    public static function getSalesTargetYearDD()
    {
        $current_year = intval(date('Y'));
        $ten_years = $current_year + 10;

        $years = [];

        for ($i = $current_year; $i < $ten_years; $i++) {

            $years[] = $i;
        }

        return $years;
    }

    public static function getMonthsDD()
    {
        $months = [];

        for ($i = 1; $i <= 12; $i++) {

            $months[] = Carbon::create()->months($i)->format("F");
        }

        return $months;
    }

    public static function getPackageDuration($package)
    {
        $start = Carbon::createFromDate($package->start_date);
        $end = Carbon::createFromDate($package->end_date);

        return $start->diffInDays($end);
    }

    public static function getClientDiscount($package)
    {
        if ($package->discount_type == "flat") {
            return $package->client_discount;
        } else {

            return round($package->price * ($package->client_discount / 100));
        }
    }

    public static function getAllEmployeeSalesTarget()
    {
        $employees = User::all();


        $targets = [];

        foreach ($employees as $employee) {

            if ($employee->hasRole('sales')) {
                $targets[$employee->name] = self::getSalesTargetByEmployeeId($employee->id);
            }
        }

        return $targets;
    }

    public static function getEmployeeSalesTarget()
    {
        $employees = User::where('manager_id', Auth::user()->id)->get();

        $targets = [];

        foreach ($employees as $employee) {

            $targets[$employee->name] = self::getSalesTargetByEmployeeId($employee->id);
        }

        return $targets;
    }

    public static function getSalesTargetByEmployeeId($employee_id)
    {
        $targets = EmployeeSalesTarget::where('employee_id', $employee_id)
            ->where('year', intval(date("Y")))
            ->first();

        if ($targets) {

            $targets = $targets->toArray();
        } else {
            return [];
        }

        $sales = [];

        for ($i = 1; $i <= 12; $i++) {

            $month = strtolower(Carbon::create(date("Y-") . $i . "-1")->format("F"));

            $total_sale = Proposal::where('created_by', $employee_id)
                ->whereMonth('booked_at', $i)
                ->whereYear('booked_at', intval(date("Y")))
                ->where('status', 'Booked')
                ->sum('proposal_cost');

            if ($targets[$month] > 0) {

                if ($total_sale > 0) {
                    $sales[$month]['percentage'] = round(($total_sale / $targets[$month]) * 100);
                    $sales[$month]['achieved'] = $total_sale;
                    $sales[$month]['total'] = $targets[$month];
                } else {
                    $sales[$month]['percentage'] = 0;
                    $sales[$month]['achieved'] = $total_sale;
                    $sales[$month]['total'] = $targets[$month];
                }
            } else {
                $sales[$month]['percentage'] = 'NA';
                $sales[$month]['achieved'] = $total_sale;
                $sales[$month]['total'] = $targets[$month];
            }
        }

        return $sales;
    }

    public static function getUsersForReports()
    {

        if (Auth::user()->hasRole('sales')) {
            return [Auth::user()->id];
        } else if (Auth::user()->hasRole('manager')) {
            $ids = Common::getEmployeesIds();
            array_push($ids, Auth::user()->id);

            return $ids;
        }

        return [];
    }

    public static function isManager($user_id)
    {
        return User::where('manager_id', $user_id)->count();
    }

    public static function readNotification($id)
    {
        $notification = BellNotification::find($id);

        if ($notification) {

            $notification->status = "read";
            $notification->save();
        }
    }

    public static function createNotification($notify_user_id, $data, $title = '')
    {
        if (empty($title))
            $title = "General Notification";
        //dd($title);
        $photoUrl = "";

        if (Auth::user()) {

            $title = Auth::user()->name;
            $photoUrl = (Auth::user()->profile_photo_url) ? Auth::user()->profile_photo_url : $photoUrl;
        } elseif (array_key_exists('title', $data)) {

            $title = $data['title'];
            $photoUrl = self::getOwnCompanyData()->favicon;
        }

        $notification = new BellNotification();

        $notification->notify_user_id = $notify_user_id;
        $notification->row_id = $data['row_id'];
        $notification->type = "System";

        $notification->title = $title;
        $notification->message = $data['message'];
        $notification->action_url = $data['url'];
        $notification->photo_url = $photoUrl;
        $notification->status = 'unread';
        // dd( $notification);
        $notification->save();

        // send push notification to mobile
        // if (env('FCM_SERVER_KEY') && env('FCM_SERVER_KEY') != "") {
        self::sendMobilePushNotification($notification);
        // }
    }

    private static function sendMobilePushNotification($notification)
    {
        $user = User::find($notification->notify_user_id);

        if ($user->hasRole("client") && $user->push_token != "") {

            $payload = [
                'notification' => [
                    'title' => $notification->title,
                    'body' => $notification->message,
                    'sound' => "default"
                ],
                'data' => null
            ];

            // check for multiple devices
            $devices = $user->devices()->where("push_token", '!=', "")->get();
            if ($devices) {
                foreach ($devices as $device) {
                    $push =  PushNotificationHelper::send($device->push_token, $payload);
                }
            } else {
                $push = PushNotificationHelper::send($user->push_token, $payload);
            }
            //dd($push);
        }
    }

    public static function deleteNotification($row_id, $notificationType)
    {
        BellNotification::where('row_id', $row_id)->where('type', $notificationType)->delete();
    }

    public static function deleteAllUserNotifications($user_id)
    {
        return BellNotification::where('notify_user_id', $user_id)->delete();
    }

    public static function getNotifications($user_id)
    {
        return BellNotification::where('notify_user_id', $user_id)->orderBy('id', 'desc')->get();
    }

    public static function getNewClientAccountNumber($type, $isCrypto = false)
    {
        /**
         * Enhancement in Account-Number generation flow for the problem faced by Ronie Chai.
         * This will avoid regenerating an already deleted Account-Number.
         *
         * Problem:
         *      - Created a new client (let's say Client-X) from admin, which generated and sent the Account details to the client via email.
         *      - Deleted that Client-X according to the Chairman's order.
         *      - Created a new client, let's say Client-Y, for that specific company.
         *      - The Account-Numbers generated for Client-X and Client-Y were the same.
         *
         * Replacing the previous Account-Number calculation, i.e., (individual/company Acc# Prefix) + (individual/company accounts 'count' + 1)
         * with (individual/company Acc# Prefix) + (the last 'id' from accounts table)
         */
        $lastAccount = Account::select("id")->orderBy('id', 'desc')->first();
        if ($lastAccount) {
            $lastId = $lastAccount->id;
        } else {
            $lastId = 0;
        }

        if (!$isCrypto) {
            if ($type == "individual") {
                $number = 145500756000 + ($lastId ? ($lastId + 1) : 1);
            } elseif ($type == "company") {
                $number = 155500756000 + ($lastId ? ($lastId + 1) : 1);
            }
        } else {
            $number = Carbon::now()->timestamp . ($lastId ? ($lastId + 1) : 1);
        }

        $ownCompany = self::getOwnCompanyData();

        return ($ownCompany->account_number_prefix ? $ownCompany->account_number_prefix : '') . $number;
    }

    public static function getCurrencyName($currency_id)
    {
        $currency = Currency::find($currency_id);

        if ($currency) {
            return $currency->name;
        }
    }

    public static function getCurrencyIdByShortCode($code)
    {
        $currency = Currency::where("short", $code)->first();

        if ($currency) {
            return $currency->id;
        }
    }

    public static function getCurrencyIdByISO2Code($code)
    {
        $country = Country::where("iso", $code)->first();

        if ($country) {
            return $country->id;
        }
    }

    public static function getSEPACurrencyIdByShortCode($code)
    {
        $currency = Currency::where("short", 'like', "%{$code}%")
            ->where('virtual_iban_provider', 'openpayd')
            ->first();

        if ($currency) {
            return $currency->id;
        }
    }

    public static function getCurrencyShort($currency_id)
    {
        $currency = Currency::find($currency_id);

        if ($currency) {
            return $currency->short;
        }
    }

    public static function getCurrencyNameWithShort($currency_id)
    {
        $currency = Currency::find($currency_id);

        if ($currency) {
            return $currency->name . " (" . $currency->short . ")";
        }
    }

    public static function createClientUser($client)
    {
        // check if email is not existing
        if ($client->type == "individual") {
            $user = User::where("email", $client->individual->email_address)->first();
        } else {
            $user = User::where("email", $client->company->contact->email_address)->first();
        }

        if (!$user) {
            $user = new User();

            // create random password
            $password = str::random(8) . "@" . rand(100, 500);

            $user->code = $client->code;
            $user->client_id = $client->id;
            if ($client->type == "individual") {
                $user->name = $client->name;
            } else {
                $user->name = ($client->contact) ? $client->contact->name : $client->name;
            }

            $user->email = ($client->type == "individual") ? $client->individual->email_address : $client->company->contact->email_address;
            $user->password = bcrypt($password);

            $user->email_verified_at = Carbon::now();

            $user->save();

            if (!$client->parent_id) {
                // notify user email and password
                EmailNotification::notifyUserCredentials($user, $password);
            } else {
                $user->locked = 1;
                $user->save();
            }
        } else {
            if ($user->email_verified_at == null) {
                $user->email_verified_at = Carbon::now();
                $user->save();
            }
        }

        // attach client role
        if (!$user->hasRole("client")) {
            $user->assignRole("client");
        }

        // map user to client
        Common::addUserToClient($client, $user);

        foreach ($user->roles as $role) {
            if ($role->name != "client") {
                $user->removeRole($role->name);
            }
        }
    }

    public static function notifyAndCreateClientUser($client)
    {
        // check if email is not existing
        if ($client->type == "individual") {
            $user = User::where("email", $client->individual->email_address)->first();
        } else {
            $user = User::where("email", $client->company->contact->email_address)->first();
        }

        // create random password
        $password = str::random(8) . "@" . rand(100, 500);

        if (!$user) {
            $user = new User();

            $user->code = $client->code;
            $user->client_id = $client->id;
            if ($client->type == "individual") {
                $user->name = $client->name;
            } else {
                $user->name = ($client->contact) ? $client->contact->name : $client->name;
            }

            $user->email = ($client->type == "individual") ? $client->individual->email_address : $client->company->contact->email_address;
            $user->password = bcrypt($password);

            $user->save();
        } else {
            $user->password = bcrypt($password);
            $user->save();
        }

        // attach client role
        if (!$user->hasRole("client")) {
            $user->assignRole("client");
        }

        foreach ($user->roles as $role) {
            if ($role->name != "client") {
                $user->removeRole($role->name);
            }
        }

        // map user to client
        Common::addUserToClient($client, $user);

        // notify user email and password
        EmailNotification::notifyUserCredentials($user, $password);
    }

    public static function addUserToClient($client, $user)
    {
        $exists = ClientUser::where("client_id", $client->id)
            ->where("user_id", $user->id)
            ->first();

        if (!$exists) {

            $cu = new ClientUser();

            $cu->client_id = $client->id;
            $cu->user_id = $user->id;

            $cu->save();
        }
    }

    /**
     * Approved ledger balance only (credits − debits). Use for statements, reports, settlement checks.
     */
    public static function getApprovedLedgerBalanceByAccountId($account_id)
    {
        $credit = AccountTransaction::where("account_id", $account_id)
            ->where("transaction_type", "Credit")
            ->whereIn("status", ["Approved"])
            ->sum("amount");

        $debit = AccountTransaction::where("account_id", $account_id)
            ->where("transaction_type", "Debit")
            ->where("status", "Approved")
            ->sum("amount");

        return $credit - $debit;
    }

    /**
     * Debit natures that reserve ledger (crypto rails). Fiat TT/cheque/internal do not.
     *
     * @return list<string>
     */
    public static function cryptoHoldNatures(): array
    {
        return ['Buy', 'Sell', 'Withdraw', 'Swap'];
    }

    /**
     * @return list<string>
     */
    public static function balanceHoldStatuses(): array
    {
        return config('balance.hold_statuses', ['Pending']);
    }

    /**
     * True when this account has an OTP-verified pending crypto debit (Buy/Sell/Withdraw/Swap or crypto_amount).
     */
    public static function accountHasPendingCryptoDebitHold($account_id, $currency_id = null): bool
    {
        $query = TRRequest::query()
            ->where('account_id', $account_id)
            ->where('transaction_type', 'Debit')
            ->where('status', 'Pending')
            ->where('otp_verified', true)
            ->where(function ($inner) {
                $inner->whereNotNull('crypto_amount')
                    ->orWhereIn('transaction_nature', self::cryptoHoldNatures());
            });

        if ($currency_id !== null) {
            $query->where('currency_id', $currency_id);
        }

        return $query->exists();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected static function applyEligiblePendingDebitAtFilters($query)
    {
        $query->where('transaction_type', 'Debit')
            ->where('status', 'Pending')
            ->where(function ($inner) {
                $inner->whereNull('tr_request_id')
                    ->orWhereHas('requestedTrn', function ($tr) {
                        $tr->where('otp_verified', true)
                            ->where('status', 'Pending');
                    });
            });
    }

    /**
     * Sum of debit exposure not yet Approved (pending AT debits + TR-only requests, no double count).
     * Holds apply only when a pending crypto debit exists on the account.
     */
    public static function getPendingDebitHoldByAccountId($account_id, $currency_id = null): float
    {
        if (! self::accountHasPendingCryptoDebitHold($account_id, $currency_id)) {
            return 0.0;
        }

        $pendingAt = AccountTransaction::query()
            ->where('account_id', $account_id);

        self::applyEligiblePendingDebitAtFilters($pendingAt);

        if ($currency_id !== null) {
            $pendingAt->where('currency_id', $currency_id);
        }

        $atHold = (float) $pendingAt->sum('amount');

        $coveredTrIds = AccountTransaction::query()
            ->where('account_id', $account_id)
            ->whereNotNull('tr_request_id');

        self::applyEligiblePendingDebitAtFilters($coveredTrIds);

        $coveredTrIds = $coveredTrIds
            ->pluck('tr_request_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        $trQuery = TRRequest::query()
            ->where('account_id', $account_id)
            ->where('transaction_type', 'Debit')
            ->where('status', 'Pending')
            ->where('otp_verified', true);

        if ($coveredTrIds !== []) {
            $trQuery->whereNotIn('id', $coveredTrIds);
        }

        if ($currency_id !== null) {
            $trQuery->where('currency_id', $currency_id);
        }

        $trHold = 0.0;
        foreach ($trQuery->with('transactionDetails')->get() as $tr) {
            $trHold += self::resolvePendingTrDebitAmount($tr);
        }

        return $atHold + $trHold;
    }

    protected static function resolvePendingTrDebitAmount(TRRequest $tr): float
    {
        $nature = $tr->transaction_nature;

        switch ($nature) {
            case 'Withdraw':
                return (float) ($tr->crypto_amount ?? $tr->amount ?? 0);
            case 'Sell':
                return (float) ($tr->crypto_amount ?? 0);
            case 'Buy':
                $detailsAmount = $tr->transactionDetails?->amount;
                if ($detailsAmount !== null && (float) $detailsAmount > 0) {
                    return (float) $detailsAmount;
                }

                return (float) ($tr->amount ?? 0);
            case 'Swap':
                return (float) ($tr->amount ?? 0);
            default:
                if ($tr->transaction_type !== 'Debit') {
                    return 0.0;
                }

                $base = (float) ($tr->amount ?? 0);
                $details = $tr->transactionDetails;
                if ($details) {
                    if ((int) $details->trn_fee_client_account_id === (int) $tr->account_id) {
                        $base += (float) ($details->trn_fee ?? 0);
                    }
                    if ((int) $details->service_fee_client_account_id === (int) $tr->account_id) {
                        $base += (float) ($details->service_fee ?? 0);
                    }
                }

                return $base;
        }
    }

    /**
     * Spendable balance: approved ledger minus pending debit holds.
     */
    public static function getAccountBalanceById($account_id)
    {
        $ledger = self::getApprovedLedgerBalanceByAccountId($account_id);
        $hold = self::getPendingDebitHoldByAccountId($account_id);

        return max(0, $ledger - $hold);
    }

    /**
     * @return array{ledger: float|int, pending_hold: float, available: float|int}
     */
    public static function getAccountBalanceBreakdownById($account_id, $currency_id = null): array
    {
        $ledger = $currency_id !== null
            ? self::getApprovedLedgerBalanceByCurrencyId($account_id, $currency_id)
            : self::getApprovedLedgerBalanceByAccountId($account_id);
        $hold = self::getPendingDebitHoldByAccountId($account_id, $currency_id);
        $available = max(0, $ledger - $hold);

        return [
            'ledger' => $ledger,
            'pending_hold' => $hold,
            'available' => $available,
        ];
    }

    /**
     * Pending debit hold attributable to a specific TR (linked pending AT and/or TR-only hold).
     */
    public static function pendingHoldAttributableToTrRequest(TRRequest $tr): float
    {
        if ($tr->transaction_type !== 'Debit' || $tr->status !== 'Pending') {
            return 0.0;
        }

        if (! (bool) $tr->otp_verified) {
            return 0.0;
        }

        if (! self::accountHasPendingCryptoDebitHold($tr->account_id, $tr->currency_id)) {
            return 0.0;
        }

        $accountId = (int) $tr->account_id;

        $atHold = (float) AccountTransaction::query()
            ->where('account_id', $accountId)
            ->where('transaction_type', 'Debit')
            ->where('status', 'Pending')
            ->where(function ($query) use ($tr) {
                $query->where('tr_request_id', $tr->id);
                if (! empty($tr->link_tr_id)) {
                    $query->orWhere('id', $tr->link_tr_id);
                }
            })
            ->sum('amount');

        if ($atHold > 0) {
            return $atHold;
        }

        $coveredByAt = AccountTransaction::query()
            ->where('account_id', $accountId)
            ->where('transaction_type', 'Debit')
            ->where('status', 'Pending')
            ->where('tr_request_id', $tr->id)
            ->exists();

        if ($coveredByAt) {
            return 0.0;
        }

        return self::resolvePendingTrDebitAmount($tr);
    }

    /**
     * Spendable balance when approving this TR (excludes this request's own pending hold).
     */
    public static function getAvailableBalanceForTrApproval(TRRequest $tr): float
    {
        $available = (float) self::getAccountBalanceById($tr->account_id);
        $release = self::pendingHoldAttributableToTrRequest($tr);

        return max(0, $available + $release);
    }

    /**
     * @return array{ledger: float|int, pending_hold: float, available: float|int, this_request_hold: float, approval_available: float|int, crypto_hold_active: bool}
     */
    public static function getAccountBalanceBreakdownForTrApproval(TRRequest $tr): array
    {
        $breakdown = self::getAccountBalanceBreakdownById($tr->account_id);
        $thisHold = self::pendingHoldAttributableToTrRequest($tr);
        $breakdown['this_request_hold'] = $thisHold;
        $breakdown['approval_available'] = max(0, (float) $breakdown['available'] + $thisHold);
        $breakdown['crypto_hold_active'] = self::accountHasPendingCryptoDebitHold($tr->account_id);

        return $breakdown;
    }

    public static function resolveTrApprovalDebitTotal(
        TRRequest $tr,
        float $principalAmount,
        ?float $serviceFee = null,
        ?float $trnFee = null,
        ?int $serviceFeeAccountId = null,
        ?int $trnFeeAccountId = null,
    ): float {
        $total = $principalAmount;

        if ($serviceFee !== null && $serviceFeeAccountId !== null
            && (int) $serviceFeeAccountId === (int) $tr->account_id) {
            $total += (float) $serviceFee;
        }

        if ($trnFee !== null && $trnFeeAccountId !== null
            && (int) $trnFeeAccountId === (int) $tr->account_id) {
            $total += (float) $trnFee;
        }

        return $total;
    }

    public static function trApprovalHasSufficientBalance(
        TRRequest $tr,
        float $principalAmount,
        ?float $serviceFee = null,
        ?float $trnFee = null,
        ?int $serviceFeeAccountId = null,
        ?int $trnFeeAccountId = null,
    ): bool {
        $total = self::resolveTrApprovalDebitTotal(
            $tr,
            $principalAmount,
            $serviceFee,
            $trnFee,
            $serviceFeeAccountId,
            $trnFeeAccountId,
        );

        return $total <= self::getAvailableBalanceForTrApproval($tr);
    }

    public static function trApprovalInsufficientBalanceMessage(
        TRRequest $tr,
        float $principalAmount,
        ?float $serviceFee = null,
        ?float $trnFee = null,
        ?int $serviceFeeAccountId = null,
        ?int $trnFeeAccountId = null,
    ): string {
        $required = self::resolveTrApprovalDebitTotal(
            $tr,
            $principalAmount,
            $serviceFee,
            $trnFee,
            $serviceFeeAccountId,
            $trnFeeAccountId,
        );
        $fees = max(0, $required - $principalAmount);
        $breakdown = self::getAccountBalanceBreakdownForTrApproval($tr);
        $max = (float) $breakdown['approval_available'];
        $fmt = static fn (float $value): string => number_format($value, 2);

        $message = sprintf(
            'Insufficient balance for this approval. Required %s (amount %s + fees %s). Maximum available for this request is %s (ledger %s).',
            $fmt($required),
            $fmt($principalAmount),
            $fmt($fees),
            $fmt($max),
            $fmt((float) $breakdown['ledger']),
        );

        if (! empty($breakdown['crypto_hold_active'])) {
            $message .= sprintf(
                ' A pending crypto debit is holding this account. Other pending OTP-verified debits: %s. This request’s own hold (included in the maximum): %s. Unverified OTP and Initial Review deposits are not held.',
                $fmt(max(0, (float) $breakdown['pending_hold'] - (float) $breakdown['this_request_hold'])),
                $fmt((float) $breakdown['this_request_hold']),
            );
        } else {
            $message .= ' There is no pending crypto debit on this account, so other pending TTs are not held. Unverified OTP and Initial Review deposits are not held.';
        }

        if ($required > $max) {
            $message .= sprintf(' Shortfall: %s.', $fmt($required - $max));
        }

        return $message;
    }

    public static function getAccountBalanceInReserves($account_id, $reserves_bank_account_id)
    {
        $clientToBank = AccountTransaction::where('account_id', $account_id)
            ->where("status", "Approved")
            ->where("transaction_nature", "MUSD Reserve")
            ->where('transaction_type', 'Debit')
            // ->where('is_deleted', false)
            ->whereHas('linkedAccTrn', function ($query) use ($reserves_bank_account_id) {
                $query->where('account_id', $reserves_bank_account_id)
                    // ->where('is_deleted', false)
                    ->where("status", "Approved")
                    ->where("transaction_nature", "MUSD Reserve")
                    ->where('transaction_type', 'Credit');
            })
            ->sum('amount');

        $bankToClient = AccountTransaction::where('account_id', $account_id)
            ->where("status", "Approved")
            ->where("transaction_nature", "MUSD Reserve")
            ->where('transaction_type', 'Credit')
            // ->where('is_deleted', false)
            ->whereHas('linkedAccTrn', function ($query) use ($reserves_bank_account_id) {
                $query->where('account_id', $reserves_bank_account_id)
                    // ->where('is_deleted', false)
                    ->where("status", "Approved")
                    ->where("transaction_nature", "MUSD Reserve")
                    ->where('transaction_type', 'Debit');
            })
            ->sum('amount');

        return $clientToBank - $bankToClient;
    }

    public static function getApprovedLedgerBalanceByCurrencyId($account_id, $currency_id)
    {
        $credit = AccountTransaction::where("account_id", $account_id)
            ->where("currency_id", $currency_id)
            ->where("transaction_type", "Credit")
            ->where("status", "Approved");

        $debit = AccountTransaction::where("account_id", $account_id)
            ->where("currency_id", $currency_id)
            ->where("transaction_type", "Debit")
            ->where("status", "Approved");

        if (Session::get('to_date')) {
            $to = Carbon::createFromFormat("d/m/Y", Session::get('to_date'))->format("Y-m-d 23:59:59");
            $credit = $credit->where("transaction_date", "<=", $to);
            $debit = $debit->where("transaction_date", "<=", $to);
        }

        $credit = $credit->sum("amount");
        $debit = $debit->sum("amount");

        return $credit - $debit;
    }

    /**
     * Spendable balance for a currency: approved ledger (optional report to_date) minus current pending holds.
     */
    public static function getAccountBalanceByCurrencyId($account_id, $currency_id)
    {
        $ledger = self::getApprovedLedgerBalanceByCurrencyId($account_id, $currency_id);
        $hold = self::getPendingDebitHoldByAccountId($account_id, $currency_id);

        return max(0, $ledger - $hold);
    }

    public static function getClientBalanceByCurrencyId($client_id, $currency_id, $bankAccount_id = null)
    {
        try {
            $account = false;
            if ($client_id) {
                $client = Client::where("id", $client_id)->where("status", "Approved")->first();
                if ($client) {
                    $account = Account::select("id")->where("client_id", $client->id)->where("account_currency", $currency_id)->where("is_active", true)->first();
                }
            } elseif ($bankAccount_id) {
                $account = Account::find($bankAccount_id);
            }

            $balance = 0;
            // foreach ($accounts as $account) {
            // $balance += self::getAccountBalanceByCurrencyId($account->id, $currency_id);
            if ($account) {
                $balance = self::getAccountBalanceByCurrencyId($account->id, $currency_id);
            }
            // }

            return $balance;
        } catch (\Exception $e) {
            Log::error("Currency ID: " . $currency_id . " | Client ID: " . $client_id . " | Exception: " . $e->getMessage());
        }
    }

    public static function getAccountStatementPeriods()
    {
        return [
            1 => "1 Month",
            3 => "3 Months",
            6 => "6 Months",
            12 => "12 Months",
        ];
    }

    public static function getAccountBalanceTillDate($account_id, $date)
    {
        if ($date == "") {
            $date = Carbon::now();
        }

        $credit = AccountTransaction::where("account_id", $account_id)
            ->where("transaction_type", "Credit")
            ->where("transaction_date", "<", $date)
            ->where("status", "Approved")
            ->sum("amount");

        $debit = AccountTransaction::where("account_id", $account_id)
            ->where("transaction_type", "Debit")
            ->where("transaction_date", "<", $date)
            ->where("status", "Approved")
            ->sum("amount");

        return $credit - $debit;
    }

    public static function getCurrencyBalanceTillDate($currency_id, $date, $account_ids)
    {
        if ($date == "") {
            $date = Carbon::now();
        }

        $credit = AccountTransaction::where("currency_id", $currency_id)
            ->whereIn('account_id', $account_ids)
            ->where("transaction_type", "Credit")
            ->where("transaction_date", "<", $date)
            ->where("status", "Approved");

        $credit = $credit->sum("amount");

        $debit = AccountTransaction::where("currency_id", $currency_id)
            ->whereIn('account_id', $account_ids)
            ->where("transaction_type", "Debit")
            ->where("transaction_date", "<", $date)
            ->where("status", "Approved");

        $debit = $debit->sum("amount");

        return $credit - $debit;
    }

    public static function getAccountEndingBalanceTillDate($account_id, $date)
    {
        if ($date == "") {
            $date = Carbon::now();
        }

        $credit = AccountTransaction::where("account_id", $account_id)
            ->where("transaction_type", "Credit")
            ->where("transaction_date", "<=", $date)
            ->where("status", "Approved")
            ->sum("amount");

        $debit = AccountTransaction::where("account_id", $account_id)
            ->where("transaction_type", "Debit")
            ->where("transaction_date", "<=", $date)
            ->where("status", "Approved")
            ->sum("amount");

        return $credit - $debit;
    }

    public static function canUserMakeChange()
    {
        if (Auth::check()) {
            return (Auth::user()->hasRole(["operations manager", "finance manager", "super admin", "client", "draft client", "basic admin", "audit"]));
        }
    }

    public static function formatStatus($status)
    {
        switch ($status) {
            case 'Approved':
                return "<span class='badge badge-success'>" . $status . "</span>";
            case 'Pending':
                return "<span class='badge badge-primary'>" . $status . "</span>";
            case 'Rejected':
                return "<span class='badge badge-danger'>" . $status . "</span>";
        }
    }

    public static function getTotalCredit($id, $from, $to)
    {
        return AccountTransaction::where("account_id", $id)
            ->where("transaction_type", "Credit")
            ->where("transaction_date", ">=", $from)
            ->where("transaction_date", "<=", $to)
            ->where("status", "Approved")
            ->sum("amount");
    }

    public static function getTotalDebit($id, $from, $to)
    {
        return AccountTransaction::where("account_id", $id)
            ->where("transaction_type", "Debit")
            ->where("transaction_date", ">=", $from)
            ->where("transaction_date", "<=", $to)
            ->where("status", "Approved")
            ->sum("amount");
    }

    public static function getAccountNumber($account_id)
    {
        $account = Account::find($account_id);
        return $account->account_number;
    }

    public static function isValidFile($file)
    {
        $allowed_ext = ["doc", "docx", "xls", "xlsx", "jpg", "jpeg", "png", "bmp", "gif"];

        $ext = $file->getClientOriginalExtension();

        if (!in_array($ext, $allowed_ext)) {
            return false;
        }

        return true;
    }

    public static function logBadFileException($file)
    {
        return redirect(url()->previous())->with("error", "The file type is not allowed.");
    }

    public static function getAccountsCountByCurrencyId($id, $excludeBanks = false)
    {
        $accounts = Account::where("account_currency", $id)
            ->where("is_active", true)
            ->where("client_id", '>', 0);

        if ($excludeBanks) {
            $excludedBanks = self::getAccountIdsWithoutBanks();
            $accounts = $accounts->whereIn('id', $excludedBanks)->count();
        } else {
            $accounts = $accounts->count();
        }

        return $accounts;
    }

    public static function getClientIdsByUserId($user_id, $includeAssociatedAccounts = false)
    {
        if ($includeAssociatedAccounts) {
            return ClientUser::where("user_id", $user_id)->pluck("client_id")->toArray();
        } else {
            return ClientUser::where("client_users.user_id", $user_id)
                ->LeftJoin("clients as parent", "client_users.client_id", "=", "parent.id")
                ->where(function ($query) {
                    $query->where('parent.parent_id', 0)->where("parent.status", "Approved");
                })->pluck("parent.id")->toArray();
        }
    }

    public static function getClientIdsByClientId($client_id)
    {
        $user_id = ClientUser::where("client_id", $client_id)->pluck("user_id")->first();
        return ClientUser::where("user_id", $user_id)->pluck("client_id")->toArray();
    }

    public static function checkIfOwnAccount($debit_account)
    {
        $client_ids = Common::getClientIdsByUserId(Auth::user()->id);
        $account = Account::whereIn("client_id", $client_ids)
            ->where("id", $debit_account->id)
            ->first();

        return ($account) ? true : false;
    }

    public static function getAccountByAccountNumber($account_number)
    {
        $account = Account::where("account_number", $account_number)->first();

        return $account ?? null;
    }

    public static function generateOnBoardingPDF($client, $configFiles = null)
    {
        if ($client->type == "individual") {
            return self::getIndividualOnboardingPDFs($client, $configFiles);
        } elseif ($client->type == "company") {
            return self::getCompanyOnboardingPDFs($client, $configFiles);
        }
    }

    public static function getIndividualOnboardingPDFs($client, $configFiles = null)
    {
        $pdfs = [];
        $pdfOptionsService = app(\App\Services\OnboardingPdfOptionsService::class);
        $pdfOptions = $pdfOptionsService->resolve(self::getOwnCompanyData());

        foreach ($configFiles as $file) {
            if (!$pdfOptionsService->shouldGenerate($pdfOptions, $file->identifier, 'individual')) {
                continue;
            }

            switch ($file->identifier) {
                case 'application_form':
                    // generate individual onboarding pdf
                    $individual = $client->individual;
                    $view = view("pdfs.on-boarding.individual", compact('individual', 'client', 'pdfOptions'));
                    $pdfFile = PDF::loadHTML($view)->setPaper('a4');

                    $pdfFile->save(storage_path('app/onBoardingPdfs/' . $client->id . '/' . $file->id . '.pdf'));

                    $pdfs[$file->id] = storage_path('app/onBoardingPdfs/' . $client->id . '/' . $file->id . '.pdf');
                    break;
                case 'letter_of_indemnity':
                    // generate letter of indemnity
                    $view = view("pdfs.on-boarding.letter-of-indemnity", compact('client', 'pdfOptions'));
                    $pdfFile = PDF::loadHTML($view)->setPaper('a4');

                    $pdfFile->save(storage_path('app/onBoardingPdfs/' . $client->id . '/' . $file->id . '.pdf'));

                    $pdfs[$file->id] = storage_path('app/onBoardingPdfs/' . $client->id . '/' . $file->id . '.pdf');

                    break;
                default:
                    break;
            }
        }

        return $pdfs;
    }

    public static function getCompanyOnboardingPDFs($client, $configFiles = null)
    {
        $pdfs = [];
        $pdfOptionsService = app(\App\Services\OnboardingPdfOptionsService::class);
        $pdfOptions = $pdfOptionsService->resolve(self::getOwnCompanyData());

        foreach ($configFiles as $file) {
            if (!$pdfOptionsService->shouldGenerate($pdfOptions, $file->identifier, 'company')) {
                continue;
            }

            switch ($file->identifier) {
                case 'application_form':
                    // generate company onboarding pdf
                    $view = view("pdfs.on-boarding.company", compact('client', 'pdfOptions'));
                    $pdfFile = PDF::loadHTML($view)->setPaper('a4');

                    $pdfFile->save(storage_path('app/onBoardingPdfs/' . $client->id . '/' . $file->id . '.pdf'));

                    $pdfs[$file->id] = storage_path('app/onBoardingPdfs/' . $client->id . '/' . $file->id . '.pdf');
                    break;
                case 'letter_of_indemnity':
                    // generate letter of indemnity
                    $view = view("pdfs.on-boarding.letter-of-indemnity", compact('client', 'pdfOptions'));
                    $pdfFile = PDF::loadHTML($view)->setPaper('a4');

                    $pdfFile->save(storage_path('app/onBoardingPdfs/' . $client->id . '/' . $file->id . '.pdf'));

                    $pdfs[$file->id] = storage_path('app/onBoardingPdfs/' . $client->id . '/' . $file->id . '.pdf');
                    break;
                case 'board_of_resolution':
                    // generate board resolution pdf
                    $view = view("pdfs.on-boarding.board-resolution", compact('client', 'pdfOptions'));
                    $pdfFile = PDF::loadHTML($view)->setPaper('a4');

                    $pdfFile->save(storage_path('app/onBoardingPdfs/' . $client->id . '/' . $file->id . '.pdf'));

                    $pdfs[$file->id] = storage_path('app/onBoardingPdfs/' . $client->id . '/' . $file->id . '.pdf');
                    break;
                default:
                    break;
            }
        }
        return $pdfs;
    }

    public static function uploadIndividualSignedPDF($client, $nfile, $file_id)
    {
        // delete existing file if exists
        $file = ClientIndividualFile::where("client_id", $client->id)
            ->where("individual_id", $client->individual->id)
            ->where("file_id", $file_id)
            ->first();

        if ($file) {
            S3Helper::fileDelete($file->url);
            $file->delete();
        }

        // upload new file
        $content = file_get_contents($nfile);
        $url = S3Helper::fileUpload(null, "client-onboarding-files/clients/individual/" . $client->id, $content, 'pdf');

        if ($url) {

            $new_file = new ClientIndividualFile();

            $new_file->client_id = $client->id;
            $new_file->individual_id = $client->individual->id;
            $new_file->file_id = $file_id;
            $new_file->url = $url;
            $new_file->created_by = $client->created_by ?? null;
            $new_file->save();
        } else {
            Log::error("Failed to upload Individual PDF for Client ID: {$client->id}, File ID: {$file_id}");
        }
    }

    public static function uploadCompanySignedPDF($client, $nfile, $file_id)
    {
        // delete existing file if exists
        $file = ClientCompanyFile::where("client_id", $client->id)
            ->where("company_id", $client->company->id)
            ->where("file_id", $file_id)
            ->first();

        if ($file) {
            S3Helper::fileDelete($file->url);
            $file->delete();
        }

        // upload new file
        $content = file_get_contents($nfile);
        $url = S3Helper::fileUpload(null, "client-onboarding-files/clients/company/" . $client->id, $content, 'pdf');

        if ($url) {

            $new_file = new ClientCompanyFile();

            $new_file->client_id = $client->id;
            $new_file->company_id = $client->company->id;
            $new_file->file_id = $file_id;
            $new_file->url = $url;
            $new_file->created_by = $client->created_by ?? null;
            $new_file->save();
        } else {
            Log::error("Failed to upload Company PDF for Client ID: {$client->id}, File ID: {$file_id}");
        }
    }

    public static function verifyRequestOTP($otp, $request_id, $type = 'TRANSACTION_REQUEST')
    {
        // validate OTP code
        $otp = OTPCode::where('user_id', auth()->user()->id)
            ->where('otp', $otp)
            ->where("row_id", $request_id)
            ->where("type", $type)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->first();

        return $otp ?? null;
    }

    public static function deletePreviousOTP($row_id, $type = 'TRANSACTION_REQUEST')
    {
        OTPCode::where('user_id', auth()->user()->id)
            ->where("row_id", "$row_id")
            ->where("type", $type)
            ->delete();
    }

    public static function getOwnCompanyData()
    {
        return OwnCompany::first();
    }

    public static function validTime($time)
    {
        if (preg_match("/^(?:2[0-4]|[01][1-9]|10):([0-5][0-9])$/", $time))
            return true;
        else
            return false;
    }

    public static function getEnabledModules()
    {
        $data = Module::where('is_enabled', true)->select('name')->get()->toArray();
        $modules = [];

        foreach ($data as $key => $value) {
            $modules[] = $value['name'];
        }

        return $modules;
    }

    public static function getModulesConfig($name = null)
    {
        $module = Module::where('is_enabled', true)->where('name', $name)->first();
        $module_config = $module->configuration()->first();
        return unserialize($module_config->configurations);
    }

    public static function isModuleEnabled($module_name = '')
    {
        if (empty($module_name) || !self::isDatabaseAvailable()) {
            return false;
        }

        try {
            $data = Module::where('is_enabled', true)->where('name', $module_name)->select('id')->first();

            return !empty($data->id);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Whether the application can reach the database and required tables exist.
     * Safe to call during composer/artisan bootstrap when no DB is configured.
     */
    public static function isDatabaseAvailable(): bool
    {
        static $available = null;

        if ($available !== null) {
            return $available;
        }

        try {
            DB::connection()->getPdo();
            $available = Schema::hasTable('modules');
        } catch (\Throwable) {
            $available = false;
        }

        return $available;
    }

    /**
     * Whether a module is physically installed — its code exists under the
     * App\Modules namespace (app/Modules/<Name>). This is independent of the
     * enabled/disabled toggle, so historical data owned by a module stays
     * viewable even when the module is switched off.
     */
    public static function isModuleAvailable($module_name = '')
    {
        if (empty($module_name)) {
            return false;
        }

        return is_dir(app_path('Modules' . DIRECTORY_SEPARATOR . $module_name));
    }

    public static function showClientCrypto()
    {
        $clientUsers = ClientUser::select('client_id')->where('user_id', Auth::user()->id)->get();

        // if (!$clientUsers || !$clientUsers->client_id) {
        //     return false;
        // }

        if (!count($clientUsers)) {
            return false;
        }

        $cryptoCurrencyIds = Currency::select('id')
            ->where('is_active', true)
            ->where('is_crypto', true)
            ->get();

        $linkedClientsAccounts = [];
        foreach ($clientUsers as $clientUser) {
            $cryptoAccounts = Account::where('is_active', true)
                ->where('client_id', $clientUser->client->id)
                // ->where('client_id', Client::find($clientUsers)->first()->id)
                ->whereIn('account_currency', $cryptoCurrencyIds)
                ->get();

            if ($cryptoAccounts->count() != 0) {
                $linkedClientsAccounts[] = $cryptoAccounts;
            }
        }

        if (count($linkedClientsAccounts) == 0 || $cryptoCurrencyIds->count() == 0) {
            return false;
        } else {
            return true;
        }
    }

    // public static function formatNumber($number, $isCrypto)
    // {
    //     if ($isCrypto) {
    //         return number_format($number, strlen(substr(strrchr($number, '.'), 1)));
    //     } else {
    //         return number_format($number, 2);
    //     }
    // }

    public static function formatNumber($number, $isCrypto = false): string
    {
        if ($number === null || $number === '' || !is_numeric($number)) {
            return '0.00';
        }

        if (!$isCrypto) {
            return number_format((float) $number, 2);
        }

        $plain = self::toPlainDecimal($number);
        $dotPos = strpos($plain, '.');
        $fraction = $dotPos === false ? '' : rtrim(substr($plain, $dotPos + 1), '0');
        $decimals = max(2, min(18, strlen($fraction)));

        return number_format((float) $plain, $decimals, '.', ',');
    }

    /**
     * Decimal string without scientific notation (e.g. 0.000001 not 1.0E-6).
     */
    public static function toPlainDecimal($number): string
    {
        if ($number === null || $number === '' || !is_numeric($number)) {
            return '0';
        }

        $str = trim((string) $number);
        if ($str !== '' && stripos($str, 'e') === false) {
            return $str;
        }

        $formatted = sprintf('%.18F', (float) $number);
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }

    public static function showCardManagement()
    {
        $clients = Auth::user()->linkClients;
        foreach ($clients as $cl) {
            if ($cl->client->status == 'Rejected') {
                return false;
            }
            if (count($cl->client->cardLinks)) {
                return true;
            }
        }

        return false;
    }

    public static function validateTime($time)
    {
        $array = explode(":", $time);

        if (!isset($array[1]) || $array[0] < 0 || $array[0] > 23) {
            return false;
        }

        if (!isset($array[1]) || $array[1] < 0 || $array[1] > 59) {
            return false;
        }

        return $array[0] . ":" . $array[1] . ":00";
    }

    public static function isCryptoCurrency($currency_id)
    {
        $currency = Currency::find($currency_id);

        if ($currency) {
            return ($currency->is_crypto) ? true : false;
        }

        return null;
    }

    public static function uploadSupportingDocuments($request, $trn, $client, $document_type = 'supporting_documents')
    {
        // upload supporting documents
        if ($request->hasFile($document_type)) {
            // if ($trn->docs && $trn->docs->count() > 0 && Auth::user()->hasRole('client')) {
            //     foreach ($trn->docs as $doc) {
            //         S3Helper::fileDelete($doc->url);
            //         $doc->delete();
            //     }
            // }

            foreach ($request->file($document_type) as $file) {

                // validate file
                if (!FileFirewall::isAllowedType($file)) {
                    return FileFirewall::logBadFileException($file);
                }

                $url = S3Helper::fileUpload($file, "transaction/requests/docs/" . $client->id);

                if ($url) {
                    $doc = new TRRequestDoc();

                    $doc->transaction_id = $trn->id;
                    $doc->url = $url;
                    $doc->is_payment = $document_type == "supporting_documents" ? false : true;

                    $doc->save();
                }
            }
        }
    }

    public static function closeRestoreClient($client, $closeReason, $status = "Closed")
    {
        $client->status = $status;
        $client->closed_at = $status == "Closed" ? Carbon::now() : null;
        $client->closed_by = $status == "Closed" ? Auth::user()->id : null;
        $client->close_reason = $status == "Closed" ? $closeReason : null;
        $client->approved_at = $status == "Closed" ? null : Carbon::now();
        $client->approved_by = $status == "Closed" ? null : Auth::user()->id;

        $client->save();

        // Deactivate client accounts
        self::approveRejectClientAccounts($client, $status == "Closed" ? 0 : 1);

        // Lock/Unlock based on the client's email fetch directly from users table
        $user_email = $client->individual?->email_address ?? $client->company?->contact->email_address;

        $user = User::where('email', $user_email)->first();
        if ($user && !$client->parent_id) {
            self::lockUserAccount($user->id, $status == "Closed");
        }

        // if this a parent with children,apply the same action to them
        if (!$client->parent_id && $client->children()->count() > 0) {
            foreach ($client->children as $child) {
                self::closeRestoreClient($child, $closeReason, $status);
            }
        }
    }

    public static function approveRejectClientAccounts($client, $type)
    {
        if ($client->type == "individual") {
            $accounts = $client->individual->accounts;
        } else {
            $accounts = $client->company->accounts;
        }

        $virtual_bank_errors = [];
        $hasAPIrole = User::whereHas('roles', function ($query) {
            $query->where('name', 'client');
        })
            ->whereHas('linkClients', function ($query) use ($client) {
                $query->where('client_id', $client->id);
            })
            ->exists();

        foreach ($accounts as $account) {
            if (Common::isModuleEnabled($account->currency->virtual_iban_provider) && $hasAPIrole && $type == 1) {
                switch ($account->currency->virtual_iban_provider) {
                    case 'openpayd':
                        $result = self::generateOpenpaydAccountIBAN($account->id);
                        break;
                    case 'lorum':
                        $result = self::generateLorumAccountIBAN($account->id);
                        break;
                    case 'zenus':
                        $result = self::generateZenusAccountIBAN($account->id);
                        break;
                    default:
                        $result = [
                            'success' => false,
                            'message' => 'Virtual IBAN provider not supported.'
                        ];
                        break;
                }

                if (!$result['success']) {
                    $account->is_active = 0;
                    $virtual_bank_errors[] = $result['message'];
                }
            }

            $account->is_active = $type;
            $account->save();
        }
        return $virtual_bank_errors;
    }

    public static function lockUserAccount($user_id, $status)
    {
        $user = User::find($user_id);

        if (count($user->roles) == 1 && $user->roles[0]->name == "client") {
            $user->locked = $status;
            $user->save();
        }
    }

    public static function getBalanceCertificatePDF($service)
    {
        $pdf = PDF::loadView('pdfs.client-service.balance-certificate', compact('service'))
            ->setPaper('a4', 'portrait');
        return $pdf->output();
    }

    public static function logError($e, $method, $line, $entity = null)
    {
        // $backtrace = $e->getTrace();
        // $source_details = '';

        $user_name = Auth::user()->name;
        $source_details = ' | Source Method: ' . $method . ' | Line#: ' . $line . ' | Logged-in User: ' . $user_name;
        // Todo: Log the exception properly, the following is just a simple logging.
        Log::error(
            'Exception: ' . $e->getMessage() . $source_details
        );

        if ($entity) {
            Log::debug('Associated Entity: ' . print_r($entity, true));
        }
    }

    public static function getAccountIdsWithoutBanks()
    {
        $clientIds = Client::select('id')->where('status', 'Approved')->where('is_bank', false)->pluck('id')->toArray();
        $bank_account_ids = Account::select('id')->whereNull('bank_id')
            ->whereIn('client_id', $clientIds)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        return $bank_account_ids;
    }

    public static function validateDate($fromDate, $toDate)
    {
        $dateRegex = '/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/(15\d{2}|[2-9]\d{3})$/';
        return preg_match($dateRegex, $fromDate) && preg_match($dateRegex, $toDate);
    }

    public static function generateUniqueString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charLength = strlen($characters);
        $uniqueString = '';

        // Add a timestamp to ensure uniqueness
        $uniqueString .= time();

        // Generate a random string using available characters
        for ($i = 0; $i < $length; $i++) {
            $uniqueString .= $characters[rand(0, $charLength - 1)];
        }

        return $uniqueString;
    }

    public static function transferMoneyToParentAccount($transaction, $parentClientId, $request)
    {
        $clientAccount = Account::where('account_currency', $transaction->currency_id)->where('client_id', $parentClientId)->first();

        $trn_date = Carbon::createFromFormat("Y-m-d H:i:s", $transaction->transaction_date);
        $credit_transaction_date = $trn_date->addSeconds(10)->format("Y-m-d H:i:s");
        $debit_transaction_date = $trn_date->addSeconds(20)->format("Y-m-d H:i:s");

        // create client transaction
        $req = new TRRequest();

        // create debit transaction
        $req->account_id = $transaction->account_id;
        $req->currency_id = $transaction->currency_id;
        $req->transaction_type = "Debit";
        $req->transaction_nature = "Internal";
        $req->transaction_date = $transaction->transaction_date;
        $req->amount = $transaction->amount;
        $req->ex_rate = 1;
        $req->receiver_account_number = $clientAccount->account_number;
        $req->remarks = "Internal Transfer - FROM " . $transaction->account->title . " (" . $transaction->account->account_number . ") TO " . $clientAccount->title . " (" . $clientAccount->account_number . ")";
        $req->status = "Approved";
        $req->created_by = Auth::user()->id ?? 0;

        $req->save();

        // Common::uploadSupportingDocuments($request, $req, $client);

        // create debit transaction
        $debitTransaction = new AccountTransaction();

        $debitTransaction->account_id = $transaction->account_id;
        $debitTransaction->tr_request_id = $req->id;
        $debitTransaction->currency_id = $transaction->currency_id;
        $debitTransaction->transaction_type = "Debit";
        $debitTransaction->transaction_nature = $transaction->transaction_nature;
        $debitTransaction->transaction_date = $debit_transaction_date;
        $debitTransaction->amount = $transaction->amount;
        $debitTransaction->ex_rate = 1;
        $debitTransaction->remarks = "Internal Transfer - FROM " . $transaction->account->title . " (" . $transaction->account->account_number . ") TO " . $clientAccount->title . " (" . $clientAccount->account_number . ")";
        $debitTransaction->status = "Approved";
        $debitTransaction->created_by = $req->created_by;
        $debitTransaction->approved_by = Auth::user()->id ?? 0;
        $debitTransaction->approved_at = Carbon::now();

        $debitTransaction->save();

        // create credit transaction
        $creditTransaction = new AccountTransaction();

        $creditTransaction->account_id = $clientAccount->id;
        $creditTransaction->tr_request_id = $req->id;
        $creditTransaction->link_tr_id = $debitTransaction->id;
        $creditTransaction->currency_id = $clientAccount->account_currency;
        $creditTransaction->transaction_type = "Credit";
        $creditTransaction->transaction_nature = $transaction->transaction_nature;
        $creditTransaction->transaction_date = $credit_transaction_date;
        $creditTransaction->amount = $transaction->amount;
        $creditTransaction->ex_rate = 1;
        $creditTransaction->remarks = "Internal Transfer - FROM " . $transaction->account->title . " (" . $transaction->account->account_number . ") TO " . $clientAccount->title . " (" . $clientAccount->account_number . ")";
        $creditTransaction->status = "Approved";
        $creditTransaction->created_by = $transaction->created_by;
        $creditTransaction->approved_by = Auth::user()->id ?? 0;
        $creditTransaction->approved_at = Carbon::now();

        $creditTransaction->save();

        // update link transaction id in the debit transaction
        $debitTransaction->link_tr_id = $creditTransaction->id;
        $debitTransaction->save();

        // approve transaction request
        $req->link_tr_id = $debitTransaction->id;
        $req->save();

        FeeHelper::reconcileMaintenanceFee($clientAccount->id, $transaction->amount, $request);
    }

    // private static function accountHasBalanceForFeeTransactions($trRequest, $feeClientAccountId, $feeAmount, $accTrnStatus = 'Pending')
    // {
    //     $balance = self::getAccountBalanceById($feeClientAccountId);
    //     Log::info(__LINE__ . ' || trRequest ID: ' . $trRequest->id . ' | feeClientAccountId: ' . $feeClientAccountId . ' | balance: ' . $balance . ' | feeAmount: ' . $feeAmount);

    //     // dd(['Balance' => $balance, 'Transaction Type' => $trRequest->transaction_type, 'Transaction Nature' => $trRequest->transaction_nature, 'feeClientAccountId' => $feeClientAccountId, 'trRequestAccountId' => $trRequest->account->id/*, 'trRequestReceiver_account_number' => $trRequest->receiver_account_number*/, 'trRequestAmount' => $trRequest->amount, 'feeAmount' => $feeAmount]);

    //     if ($trRequest->transaction_type == 'Debit') {
    //         if ($accTrnStatus == 'Approved') {
    //             // to-do: Fixing this flow if the feeAmount is changed/increased.
    //             $balance = $balance + $trRequest->amount + $feeAmount;
    //         }

    //         Log::info(__LINE__ . ' || trRequest ID: ' . $trRequest->id . ' | feeClientAccountId: ' . $feeClientAccountId . ' | balance: ' . $balance . ' | feeAmount: ' . $feeAmount . ' | trRequest->transaction_type: ' . $trRequest->transaction_type);
    //         if ($feeClientAccountId == $trRequest->account->id && ($trRequest->amount + $feeAmount) > $balance) {
    //             Log::info(__LINE__ . ' || trRequest ID: ' . $trRequest->id . ' | balance: ' . $balance . ' | feeAmount: ' . $feeAmount . ' | trRequest->transaction_type: ' . $trRequest->transaction_type . ' | feeClientAccountId: ' . $feeClientAccountId . ' | trRequest->account->id: ' . $trRequest->account->id . ' | trRequest->amount: ' . $trRequest->amount);
    //             return false;
    //         } elseif ($feeClientAccountId != $trRequest->account->id && $feeAmount > $balance) {
    //             Log::info(__LINE__ . ' || trRequest ID: ' . $trRequest->id . ' | balance: ' . $balance . ' | feeAmount: ' . $feeAmount . ' | trRequest->amount: ' . $trRequest->amount . ' | trRequest->transaction_type: ' . $trRequest->transaction_type . ' | feeClientAccountId: ' . $feeClientAccountId . ' | trRequest->account->id: ' . $trRequest->account->id);
    //             return false;
    //         } elseif ($trRequest->transaction_nature == 'Internal') {
    //             Log::info(__LINE__ . ' || trRequest ID: ' . $trRequest->id . ' | balance: ' . $balance . ' | feeAmount: ' . $feeAmount . ' | trRequest->amount: ' . $trRequest->amount . ' | trRequest->transaction_type: ' . $trRequest->transaction_type . ' | feeClientAccountId: ' . $feeClientAccountId . ' | trRequest->account->id: ' . $trRequest->account->id . ' | trRequest->transaction_nature: ' . $trRequest->transaction_nature);
    //             if ($feeClientAccountId == $trRequest->receiver->id && $feeAmount > ($trRequest->amount + $balance)) {
    //                 Log::info(__LINE__ . ' || trRequest ID: ' . $trRequest->id . ' | balance: ' . $balance . ' | feeAmount: ' . $feeAmount . ' | trRequest->amount: ' . $trRequest->amount . ' | trRequest->transaction_type: ' . $trRequest->transaction_type . ' | feeClientAccountId: ' . $feeClientAccountId . ' | trRequest->account->id: ' . $trRequest->account->id . ' | trRequest->transaction_nature: ' . $trRequest->transaction_nature);
    //                 return false;
    //             } elseif (
    //                 $feeClientAccountId != $trRequest->receiver->id && $feeAmount > $balance
    //             ) {
    //                 Log::info(__LINE__ . ' || trRequest ID: ' . $trRequest->id . ' | balance: ' . $balance . ' | feeAmount: ' . $feeAmount . ' | trRequest->amount: ' . $trRequest->amount . ' | trRequest->transaction_type: ' . $trRequest->transaction_type . ' | feeClientAccountId: ' . $feeClientAccountId . ' | trRequest->account->id: ' . $trRequest->account->id . ' | trRequest->transaction_nature: ' . $trRequest->transaction_nature);
    //                 return false;
    //             }
    //         }

    //         Log::info(__LINE__ . ' || trRequest ID: ' . $trRequest->id . ' | balance: ' . $balance . ' | feeAmount: ' . $feeAmount . ' | trRequest->amount: ' . $trRequest->amount . ' | trRequest->transaction_type: ' . $trRequest->transaction_type . ' | feeClientAccountId: ' . $feeClientAccountId . ' | trRequest->account->id: ' . $trRequest->account->id . ' | trRequest->transaction_nature: ' . $trRequest->transaction_nature);
    //     } else if ($trRequest->transaction_type == 'Credit') {
    //         Log::info(__LINE__ . ' || trRequest ID: ' . $trRequest->id . ' | balance: ' . $balance . ' | feeAmount: ' . $feeAmount . ' | trRequest->amount: ' . $trRequest->amount . ' | trRequest->transaction_type: ' . $trRequest->transaction_type . ' | feeClientAccountId: ' . $feeClientAccountId . ' | trRequest->account->id: ' . $trRequest->account->id . ' | trRequest->transaction_nature: ' . $trRequest->transaction_nature);
    //         if ($feeClientAccountId == $trRequest->account->id && $feeAmount > ($trRequest->amount + $balance)) {
    //             Log::info(__LINE__ . ' || trRequest ID: ' . $trRequest->id . ' | balance: ' . $balance . ' | feeAmount: ' . $feeAmount . ' | trRequest->amount: ' . $trRequest->amount . ' | trRequest->transaction_type: ' . $trRequest->transaction_type . ' | feeClientAccountId: ' . $feeClientAccountId . ' | trRequest->account->id: ' . $trRequest->account->id . ' | trRequest->transaction_nature: ' . $trRequest->transaction_nature);
    //             return false;
    //         } elseif ($feeClientAccountId != $trRequest->account->id && $feeAmount > $balance) {
    //             Log::info(__LINE__ . ' || trRequest ID: ' . $trRequest->id . ' | balance: ' . $balance . ' | feeAmount: ' . $feeAmount . ' | trRequest->amount: ' . $trRequest->amount . ' | trRequest->transaction_type: ' . $trRequest->transaction_type . ' | feeClientAccountId: ' . $feeClientAccountId . ' | trRequest->account->id: ' . $trRequest->account->id . ' | trRequest->transaction_nature: ' . $trRequest->transaction_nature);
    //             return false;
    //         } elseif ($feeClientAccountId == $trRequest->account->id && $feeAmount > $balance) {
    //             Log::info(__LINE__ . ' || trRequest ID: ' . $trRequest->id . ' | balance: ' . $balance . ' | feeAmount: ' . $feeAmount . ' | trRequest->amount: ' . $trRequest->amount . ' | trRequest->transaction_type: ' . $trRequest->transaction_type . ' | feeClientAccountId: ' . $feeClientAccountId . ' | trRequest->account->id: ' . $trRequest->account->id . ' | trRequest->transaction_nature: ' . $trRequest->transaction_nature);
    //             return false;
    //         }
    //     }

    //     Log::info(__LINE__ . ' || trRequest ID: ' . $trRequest->id . ' | balance: ' . $balance . ' | feeAmount: ' . $feeAmount . ' | trRequest->amount: ' . $trRequest->amount . ' | trRequest->transaction_type: ' . $trRequest->transaction_type . ' | feeClientAccountId: ' . $feeClientAccountId . ' | trRequest->account->id: ' . $trRequest->account->id . ' | trRequest->transaction_nature: ' . $trRequest->transaction_nature);

    //     return true;
    // }

    private static function getExistingTrnData($feeTrnId)
    {
        $existingFeeTrn['amount'] = 0;
        $existingFeeTrn['accountId'] = 0;

        if ($fee_credit_trn = AccountTransaction::find($feeTrnId)) {
            if ($fee_credit_trn->link_tr_id && $fee_debit_trn = AccountTransaction::find($fee_credit_trn->link_tr_id)) {
                $existingFeeTrn['amount'] = $fee_debit_trn->amount;
                $existingFeeTrn['accountId'] = $fee_debit_trn->account_id;
            }
        }

        return $existingFeeTrn;
    }

    private static function accountHasBalanceForFeeTransactions($trRequest, $feeClientAccountId, $feeAmount, $accTrnStatus = 'Pending', $isHidden = '', $feeTrnId = 0)
    {
        $existingFeeTrn = self::getExistingTrnData($feeTrnId);

        if (!$isHidden && ($existingFeeTrn['amount'] != floatVal($feeAmount) || $existingFeeTrn['accountId'] != $feeClientAccountId)) {
            $balance = self::getAccountBalanceById($feeClientAccountId);

            if ($trRequest->transaction_type == 'Debit') {
                if ($accTrnStatus == 'Approved') {
                    $balance += $trRequest->amount + $existingFeeTrn['amount'];
                }

                if (($feeClientAccountId == $trRequest->account->id && ($trRequest->amount + $feeAmount) > $balance) || ($feeClientAccountId != $trRequest->account->id && $feeAmount > $balance)) {
                    return false;
                }

                if ($trRequest->transaction_nature == 'Internal' && (($feeClientAccountId == $trRequest->receiver->id && $feeAmount > ($trRequest->amount + $balance)) || ($feeClientAccountId != $trRequest->receiver->id && $feeAmount > $balance))) {
                    return false;
                }
            } elseif ($trRequest->transaction_type == 'Credit' && (($feeClientAccountId == $trRequest->account->id && $feeAmount > ($trRequest->amount + $balance)) || ($feeClientAccountId != $trRequest->account->id && $feeAmount > $balance))) {
                return false;
            }
        }

        return true;
    }

    public static function handleFeesOnPayinTransactionApproval($trRequest, $parentTrn, $shouldDeductServiceFee = true, $shouldDeductTrnFee = true)
    {
        $currency = $trRequest->currency;
        $transaction_fee_rate = $currency->transaction_fee_rate ?? 0;
        $bank_fee_rate = $currency->bank_fee_rate ?? 0;
        $is_fee_hidden = false;
        $transaction_fee_client_account_id = $trRequest->account_id;
        $provider_name = $trRequest->account->bankAccount->provider_name;
        // $provider_name = $trRequest->method->module->name;

        $service_fee_bank_account_id = Bank::where('is_service_fee_bank', true)->first()->accounts->where('account_currency', $trRequest->currency_id)->first()->id;
        $trn_fee_bank_account_id = Bank::where('is_trn_fee_bank', true)->first()->accounts->where('account_currency', $trRequest->currency_id)->first()->id;
        $company_name = self::getOwnCompanyData()->short_name ?? '';

        // calculate the fee
        $transaction_amount = $trRequest->amount;
        $transaction_fee_amount = ($transaction_amount * $transaction_fee_rate) / 100;
        $bank_fee_amount = ($transaction_amount * $bank_fee_rate) / 100;

        // Currency Treshold fees
        $transaction_fee_threshold = $currency->transaction_fee_threshold ?? 0;
        $bank_fee_threshold = $currency->bank_fee_threshold ?? 0;

        // Charge calculated fee if it exceeds currency_fee_threshold; otherwise, charge the threshold.
        if ($transaction_fee_amount < $transaction_fee_threshold) {
            $transaction_fee_amount = $transaction_fee_threshold;
        }

        if ($bank_fee_amount < $bank_fee_threshold) {
            $bank_fee_amount = $bank_fee_threshold;
        }

        // Handle the Transaction Fee.
        if ($service_fee_bank_account_id && ($bank_fee_amount && $bank_fee_amount > 0) && $shouldDeductTrnFee) {
            if (
                !self::accountHasBalanceForFeeTransactions(
                    $trRequest,
                    $transaction_fee_client_account_id,
                    $bank_fee_amount,
                    $parentTrn->status,
                    $is_fee_hidden
                )
            ) {
                // throw new Exception('Insufficient balance in the client account');
                log::info("\n\n");
                log::info("");

                log::info("Fee Account: {$transaction_fee_client_account_id} ");
                Log::info("Bank Transaction Fee: {$bank_fee_amount} was not charged form Client Account Id: {$transaction_fee_client_account_id}");
                log::info("");

                log::info("\n\n");
            }

            $bank_fee_remarks = "Correspondent Charges - Inward {$currency->short} {$bank_fee_amount} ({$provider_name})";

            self::performFeeDeduction(
                $trn_fee_bank_account_id,
                $transaction_fee_client_account_id,
                $currency->id,
                $bank_fee_remarks,
                $parentTrn->id,
                $parentTrn->transaction_date,
                $trRequest,
                $bank_fee_amount,
                $is_fee_hidden,
                false,
                true,
                11
            );
        } else {
            Log::info("Bank Transaction Fee: {$bank_fee_amount} was not charged form Client Account Id: {$transaction_fee_client_account_id}");
        }

        if ($trn_fee_bank_account_id && ($transaction_fee_amount > 0 && $shouldDeductServiceFee)) {
            if (
                !self::accountHasBalanceForFeeTransactions(
                    $trRequest,
                    $transaction_fee_client_account_id,
                    $transaction_fee_amount,
                    $parentTrn->status,
                    $is_fee_hidden
                )
            ) {
                // throw new Exception('Insufficient balance in the client account');

                log::info("\n\n");
                log::info("");

                log::info("Fee Account: {$transaction_fee_client_account_id} ");
                Log::info("Bank Transaction Fee: {$transaction_fee_amount} was not charged form Client Account Id: {$transaction_fee_client_account_id}");
                log::info("");

                log::info("\n\n");
            }

            $trn_fee_remarks = "{$company_name} - Inward {$currency->short} {$transaction_fee_amount} ({$provider_name})";

            // Handle the Bank Service Fee.
            self::performFeeDeduction(
                $service_fee_bank_account_id, // Bank Account
                $transaction_fee_client_account_id, // Client Account
                $currency->id,
                $trn_fee_remarks,
                $parentTrn->id,
                $parentTrn->transaction_date,
                $trRequest,
                $transaction_fee_amount, // Fee Amount
                $is_fee_hidden,
                true,
                false,
                10
            );
        } else {
            Log::info("Transaction Fee: {$transaction_fee_amount} was not charged form Client Account Id: {$transaction_fee_client_account_id}");
        }
    }


    public static function handleFeesOnTransactionApproval($request, $trRequest, $parentTrn, $shouldDeductServiceFee = true, $shouldDeductTrnFee = true)
    {
        if ($request->get('service_fee') > 0 && $shouldDeductServiceFee) {
            if (
                !self::accountHasBalanceForFeeTransactions(
                    $trRequest,
                    $request->get('service_fee_client_account'),
                    $request->get('service_fee'),
                    $parentTrn->status,
                    $request->has('is_service_fee_trn_hidden_for_client') ? 1 : 0
                )
            ) {
                throw new Exception('Insufficient balance in the client account');
            }

            // switch ($handleBothFees) {
            //     case true:
            // Handle the Bank Service Fee.
            self::performFeeDeduction(
                $request->get('service_fee_bank_account_id'), // 279
                $request->get('service_fee_client_account'), // 73
                $request->get('service_fee_currency_id'), // 1
                $request->get('service_fee_remarks'),
                $parentTrn->id,
                $parentTrn->transaction_date,
                $trRequest,
                $request->get('service_fee'), // 50
                $request->has('is_service_fee_trn_hidden_for_client') ? 1 : 0, // 1
                // $request->get('service_fee_exchange_rate') ?? 1,
                true,
                false,
                10
            );
        }

        // Handle the Transaction Fee.
        if (
            $trRequest->transaction_nature != 'Top-up' &&
            $request->has('trn_fee_bank_account_id') &&
            ($request->has('trn_fee') &&
                $request->get('trn_fee') > 0) &&
            $shouldDeductTrnFee
        ) {
            if (
                !self::accountHasBalanceForFeeTransactions(
                    $trRequest,
                    $request->get('trn_fee_client_account'),
                    $request->get('trn_fee'),
                    $parentTrn->status,
                    $request->has('is_trn_fee_trn_hidden_for_client') ? 1 : 0
                )
            ) {
                throw new Exception('Insufficient balance in the client account');
            }

            self::performFeeDeduction(
                $request->get('trn_fee_bank_account_id'),
                $request->get('trn_fee_client_account'),
                $request->get('trn_fee_currency_id'),
                $request->get('trn_fee_remarks'),
                $parentTrn->id,
                $parentTrn->transaction_date,
                $trRequest,
                $request->get('trn_fee'),
                $request->has('is_trn_fee_trn_hidden_for_client') ? 1 : 0,
                // $request->get('trn_fee_exchange_rate') ?? 1,
                false,
                true,
                11
            );
        }
    }

    private static function performFeeDeduction(
        $creditAccountId, // 279
        $debitAccountId, // 73
        $currencyId, // 1
        $feeRemarks,
        $parentTrnId,
        $transaction_date,
        $trRequest,
        $amount, // 50
        $isHiddenForClients, // 1
        // $exchangeRate,
        $isServiceFee,
        $isTrnFee,
        $additionalSeconds // 10
    ) {

        // $credit_transaction_date = Carbon::now()->format("Y-m-d H:i:s");
        $credit_transaction_date = Carbon::parse($transaction_date)->format("Y-m-d H:i:s");
        $debit_transaction_date = Carbon::parse($transaction_date)->addSeconds($additionalSeconds)->format("Y-m-d H:i:s");
        // $debit_transaction_date = Carbon::now()->addSeconds($additionalSeconds)->format("Y-m-d H:i:s");

        if (!$isHiddenForClients) {
            // Handle the Bank Service Fee.
            $fee_debit_transaction = new AccountTransaction();

            $fee_debit_transaction->account_id = $debitAccountId;
            $fee_debit_transaction->parent_trn_id = $parentTrnId;
            $fee_debit_transaction->tr_request_id = $trRequest->id;
            $fee_debit_transaction->currency_id = $currencyId;
            $fee_debit_transaction->transaction_type = 'Debit';
            $fee_debit_transaction->transaction_nature = $trRequest->transaction_nature;
            $fee_debit_transaction->transaction_date = $debit_transaction_date;
            $fee_debit_transaction->amount = $amount;
            // $fee_debit_transaction->ex_rate = $exchangeRate;
            $fee_debit_transaction->is_fee_transaction = true;
            $fee_debit_transaction->is_service_fee = $isServiceFee;
            $fee_debit_transaction->is_trn_fee = $isTrnFee;
            $fee_debit_transaction->remarks = $feeRemarks;
            $fee_debit_transaction->status = 'Approved';
            $fee_debit_transaction->is_hidden_for_clients = $isHiddenForClients;
            $fee_debit_transaction->created_by = $trRequest->created_by;
            $fee_debit_transaction->approved_by = Auth::user()->id ?? null;
            $fee_debit_transaction->approved_at = Carbon::now();

            $fee_debit_transaction->save();

            // create client transaction
            $dt_trn = new TRRequest();

            // create debit transaction
            $dt_trn->account_id = $debitAccountId;
            $dt_trn->link_tr_id = $fee_debit_transaction->id;
            $dt_trn->currency_id = $currencyId;
            $dt_trn->transaction_type = "Debit";
            $dt_trn->transaction_nature = $trRequest->transaction_nature;
            $dt_trn->transaction_date = $debit_transaction_date;
            $dt_trn->amount = $amount;
            $dt_trn->otp_verified = true;
            $dt_trn->remarks = $feeRemarks;
            $dt_trn->status = 'Approved';
            $dt_trn->created_by = $trRequest->created_by;
            $dt_trn->approved_by = Auth::user()->id ?? null;
            $dt_trn->approved_at = Carbon::now();

            $dt_trn->save();
        }

        $fee_credit_transaction = new AccountTransaction();

        $fee_credit_transaction->account_id = $creditAccountId;
        $fee_credit_transaction->fee_debit_account_id = $debitAccountId;
        $fee_credit_transaction->parent_trn_id = $parentTrnId;
        $fee_credit_transaction->tr_request_id = $trRequest->id;
        $fee_credit_transaction->link_tr_id = isset($fee_debit_transaction) ? $fee_debit_transaction->id : null;
        $fee_credit_transaction->currency_id = $currencyId;
        $fee_credit_transaction->transaction_type = 'Credit';
        $fee_credit_transaction->transaction_nature = $trRequest->transaction_nature;
        $fee_credit_transaction->transaction_date = $credit_transaction_date;
        $fee_credit_transaction->amount = $amount; // $amount * $exchangeRate;
        // $fee_credit_transaction->ex_rate = $exchangeRate;
        $fee_credit_transaction->is_fee_transaction = true;
        $fee_credit_transaction->is_service_fee = $isServiceFee;
        $fee_credit_transaction->is_trn_fee = $isTrnFee;
        $fee_credit_transaction->remarks = $feeRemarks;
        $fee_credit_transaction->status = 'Approved';
        $fee_credit_transaction->is_hidden_for_clients = 1;
        $fee_credit_transaction->created_by = $trRequest->created_by;
        $fee_credit_transaction->approved_by = Auth::user()->id ?? null;
        $fee_credit_transaction->approved_at = Carbon::now();

        $fee_credit_transaction->save();

        if (isset($fee_debit_transaction)) {
            $fee_debit_transaction->link_tr_id = $fee_credit_transaction->id;
            $fee_debit_transaction->save();
        }

        // $trRequest->is_hidden_for_clients = $isHiddenForClients;
        // $trRequest->save();
    }

    public static function handleFeesOnTransactionUpdate($request, $parentTransaction)
    {
        if ($request->get('service_fee_trn_id')) {
            if ($request->get('service_fee') > 0) {
                if (
                    !self::accountHasBalanceForFeeTransactions(
                        $parentTransaction->requestedTrn,
                        $request->get('service_fee_client_account'),
                        $request->get('service_fee'),
                        $parentTransaction->status,
                        $request->has('is_service_fee_trn_hidden_for_client') ? 1 : 0,
                        $request->get('service_fee_trn_id')
                    )
                ) {
                    throw new Exception('Insufficient balance in the client account');
                }
            }

            self::performFeeUpdate(
                $request->get('service_fee_trn_id'),
                $request->get('service_fee_bank_account_id'),
                $request->get('service_fee_client_account'),
                $request->get('service_fee_currency_id'),
                $request->get('service_fee'),
                $request->get('service_fee_remarks'),
                $request->has('is_service_fee_trn_hidden_for_client') ?? false,
                $parentTransaction->id,
                $parentTransaction->requestedTrn->id,
                $parentTransaction->transaction_date,
                true,
                false,
                10
            );
        } else {
            self::handleFeesOnTransactionApproval($request, $parentTransaction->requestedTrn, $parentTransaction, true, false);
        }

        if ($request->get('trn_fee_trn_id')) {
            if ($request->get('trn_fee') > 0) {
                if (
                    !self::accountHasBalanceForFeeTransactions(
                        $parentTransaction->requestedTrn,
                        $request->get('trn_fee_client_account'),
                        $request->get('trn_fee'),
                        $parentTransaction->status,
                        $request->has('is_trn_fee_trn_hidden_for_client') ? 1 : 0,
                        $request->get('trn_fee_trn_id'),
                    )
                ) {
                    throw new Exception('Insufficient balance in the client account');
                }
            }

            self::performFeeUpdate(
                $request->get('trn_fee_trn_id'),
                $request->get('trn_fee_bank_account_id'),
                $request->get('trn_fee_client_account'),
                $request->get('trn_fee_currency_id'),
                $request->get('trn_fee'),
                $request->get('trn_fee_remarks'),
                $request->has('is_trn_fee_trn_hidden_for_client') ?? false,
                $parentTransaction->id,
                $parentTransaction->requestedTrn->id,
                $parentTransaction->transaction_date,
                false,
                true,
                11
            );
        } else {
            self::handleFeesOnTransactionApproval($request, $parentTransaction->requestedTrn, $parentTransaction, false, true);
        }
    }

    private static function performFeeUpdate(
        $trnId,
        $bankAccountId,
        $clientAccountId,
        $currencyId,
        $amount,
        $remarks,
        $isHiddenForClients,
        $parentTrnId,
        $parentTrRequestId,
        $transaction_date,
        $isServiceFee,
        $isTrnFee,
        $additionalSeconds,
    ) {
        $fee_credit_trn = AccountTransaction::find($trnId);
        $link_tr_id = $fee_credit_trn->link_tr_id;
        $debit_transaction_date = Carbon::parse($transaction_date)->addSeconds($additionalSeconds)->format("Y-m-d H:i:s");

        if ($amount == 0) {
            $fee_credit_trn->delete();
        } else {
            // dd('Already is_hidden_for_clients:', $fee_credit_trn->is_hidden_for_clients, 'Request has is_hidden Key:', $request->has('is_service_fee_trn_hidden_for_client'));

            $fee_credit_trn->account_id = $bankAccountId;
            $fee_credit_trn->fee_debit_account_id = $clientAccountId;
            $fee_credit_trn->currency_id = $currencyId;
            $fee_credit_trn->amount = $amount;
            $fee_credit_trn->transaction_date = $transaction_date;
            $fee_credit_trn->remarks = $remarks;

            $fee_credit_trn->save();
        }

        if ($link_tr_id) {
            $fee_debit_trn = AccountTransaction::find($link_tr_id);

            if ($isHiddenForClients || $amount == 0) {
                // $fee_credit_trn->fee_debit_account_id = null;
                if ($isHiddenForClients) {
                    $fee_credit_trn->link_tr_id = null;

                    $fee_credit_trn->save();
                }

                $trRequest = TRRequest::where('link_tr_id', $fee_debit_trn->id)->first();

                $trRequest->delete();
                $fee_debit_trn->delete();
            } else {
                $fee_debit_trn->account_id = $clientAccountId;
                $fee_debit_trn->currency_id = $currencyId;
                $fee_debit_trn->amount = $amount;
                $fee_debit_trn->transaction_date = $debit_transaction_date;
                $fee_debit_trn->remarks = $remarks;

                $fee_debit_trn->save();

                $trRequest = TRRequest::where('link_tr_id', $fee_debit_trn->id)->first();

                // create debit transaction
                $trRequest->account_id = $clientAccountId;
                $trRequest->currency_id = $currencyId;
                $trRequest->amount = $amount;
                $trRequest->remarks = $remarks;

                $trRequest->save();
            }
        } else if (!$isHiddenForClients) {

            // Create Client Transaction
            $fee_debit_trn = new AccountTransaction();

            $fee_debit_trn->account_id = $clientAccountId;
            $fee_debit_trn->link_tr_id = $fee_credit_trn->id;
            $fee_debit_trn->parent_trn_id = $parentTrnId;
            $fee_debit_trn->tr_request_id = $parentTrRequestId;
            $fee_debit_trn->currency_id = $currencyId;
            $fee_debit_trn->transaction_type = 'Debit';
            $fee_debit_trn->transaction_nature = $fee_credit_trn->transaction_nature;
            $fee_debit_trn->transaction_date = $debit_transaction_date;
            $fee_debit_trn->amount = $amount;
            $fee_debit_trn->is_fee_transaction = true;
            $fee_debit_trn->is_service_fee = $isServiceFee;
            $fee_debit_trn->is_trn_fee = $isTrnFee;
            $fee_debit_trn->remarks = $remarks;
            $fee_debit_trn->status = 'Approved';
            $fee_debit_trn->is_hidden_for_clients = false;
            $fee_debit_trn->created_by = $fee_credit_trn->created_by;
            $fee_debit_trn->approved_by = $fee_credit_trn->approved_by;
            $fee_debit_trn->approved_at = $fee_credit_trn->approved_at;

            $fee_debit_trn->save();

            $fee_credit_trn->link_tr_id = $fee_debit_trn->id;

            $fee_credit_trn->save();

            // Create Client Transaction Request
            $fee_debit_trn_request = new TRRequest();

            $fee_debit_trn_request->account_id = $clientAccountId;
            $fee_debit_trn_request->link_tr_id = $fee_debit_trn->id;
            $fee_debit_trn_request->currency_id = $currencyId;
            $fee_debit_trn_request->transaction_type = "Debit";
            $fee_debit_trn_request->transaction_nature = $fee_credit_trn->transaction_nature;
            $fee_debit_trn_request->transaction_date = $debit_transaction_date;
            $fee_debit_trn_request->amount = $amount;
            $fee_debit_trn_request->otp_verified = true;
            $fee_debit_trn_request->remarks = $remarks;
            $fee_debit_trn_request->status = 'Approved';
            $fee_debit_trn_request->created_by = $fee_credit_trn->created_by;
            $fee_debit_trn_request->approved_by = $fee_credit_trn->approved_by;
            $fee_debit_trn_request->approved_at = $fee_credit_trn->approved_at;

            $fee_debit_trn_request->save();
        }

        // dd('Already is_hidden_for_clients:', $fee_credit_trn->is_hidden_for_clients, 'Request has is_hidden Key:', $request->has('is_trn_fee_trn_hidden_for_client'));
    }

    public static function rejectLinkedTransactions($transactions, $reason)
    {
        foreach ($transactions as $trn) {
            $trn->status = "Rejected";
            $trn->rejected_by = Auth::user()->id;
            $trn->rejected_at = Carbon::now();
            $trn->reject_reason = $reason;

            $trn->save();
        }
    }

    public static function sendWebhookNotification($request, $user, $requestType, $message, $status = null)
    {
        try {
            $webhook = MerchantWebhook::where('user_id', $user->id)->first();
            $status = $status ?? $request->status;

            if ($webhook) {
                SendWebhookNotification::dispatch(
                    $webhook->url,
                    [
                        'request_id' => $request->id,
                        'request_type' => $requestType,
                        'status' => $status,
                        'message' => $message
                    ]
                );
            }
        } catch (Exception $e) {
            Log::error('Exception thrown, while posting the status, in ' . $e->getFile() . ' on Line#: ' . $e->getLine());
            Log::error('Exception Message: ' . $e->getMessage());
        }
    }

    public static function saveTransactionDetails($request, $trn, $reviewedBy = null)
    {
        // dd($request);
        Log::info('Request Data: ' . print_r($request, true));
        // Log::info('TRRequest: ' . print_r($trn, true));

        try {
            if (!empty($trn->transactionDetails)) {
                $details = TransactionDetails::where('trn_id', $trn->id)->first();
            } else {
                $details = new TransactionDetails();
            }

            // Log::info('----------------------------------------------------');
            // Log::info('----------------------------------------------------');
            // Log::info('Transaction Details: ' . print_r($details, true));

            // $user_id = Auth::user()->id ?? $trn->created_by;

            // Creator cannot be the reviewer.
            if (!is_null($reviewedBy)) {
                if ($trn->created_by == $reviewedBy) {
                    throw new Exception('The Creator of the Transaction cannot be the Reviewer!');
                }
            }

            $user_id = $reviewedBy;

            $details->user_id = $user_id;
            $details->trn_id = $trn->id;
            $details->zeroize = isset($request['is_zeroize']) ? 1 : 0;
            $details->amount = isset($request['amount']) ? $request['amount'] : null;
            $details->crypto_amount = isset($request['crypto_amount']) ? $request['crypto_amount'] : null;
            $details->ex_rate = isset($request['ex_rate']) ? $request['ex_rate'] : null;
            $details->transaction_date = $trn->transaction_date;
            $details->remarks = isset($request['remarks']) ? $request['remarks'] : null;
            $details->service_fee_client_account_id = isset($request['service_fee_client_account']) ? $request['service_fee_client_account'] : 0;
            $details->service_fee_bank_account_id = isset($request['service_fee_bank_account_id']) ? $request['service_fee_bank_account_id'] : 0;
            $details->service_fee_currency_id = isset($request['service_fee_currency_id']) ? $request['service_fee_currency_id'] : 0;
            $details->service_fee = isset($request['service_fee']) ? $request['service_fee'] : 0;
            $details->service_fee_remarks = isset($request['service_fee_remarks']) ? $request['service_fee_remarks'] : "";
            $details->is_service_fee_trn_hidden_for_client = isset($request['is_service_fee_trn_hidden_for_client']) ? 1 : 0;
            $details->trn_fee_client_account_id = isset($request['trn_fee_client_account']) ? $request['trn_fee_client_account'] : 0;
            $details->trn_fee_bank_account_id = isset($request['trn_fee_bank_account_id']) ? $request['trn_fee_bank_account_id'] : 0;
            $details->trn_fee_currency_id = isset($request['trn_fee_currency_id']) ? $request['trn_fee_currency_id'] : 0;
            $details->trn_fee = isset($request['trn_fee']) ? $request['trn_fee'] : 0;
            $details->trn_fee_remarks = isset($request['trn_fee_remarks']) ? $request['trn_fee_remarks'] : "";
            $details->is_trn_fee_trn_hidden_for_client = isset($request['is_trn_fee_trn_hidden_for_client']) ? 1 : 0;

            // Log::info("\n\n\n\n\n\n\n\n\n");
            // Log::info('----------------------------------------------------');
            // Log::info('Transaction Details: ' . print_r($details, true));
            // Log::info("\n\n\n\n\n\n\n\n\n");

            // $details->save();
            if ($details->save()) {
                Log::info('The Details have been saved successfully');
                // Log::info('----------------------------------------------------');
                // Log::info('Old Details: ' . print_r($details->getOriginal(), true));
                // Log::info('Changed Details: ' . print_r($details->getAttributes(), true));
            } else {
                Log::info('Details not saved');
                // dd($details->errors);
                throw new Exception('The Transaction Details have not been saved!');
            }
            // dd(get_class($details), $details->getTable());
            // \Illuminate\Support\Facades\DB::listen(function ($query) {
            //     // dd($query->sql);  // Check the generated query
            //     dd([
            //         'sql' => $query->sql,
            //         'bindings' => $query->bindings,
            //         'full_sql' => vsprintf(str_replace('?', '%s', $query->sql), $query->bindings) // Generates the full query with values
            //     ]);
            // });
        } catch (Exception $e) {
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            Log::error('------------------------------------------------');

            // throw new Exception($e->getMessage() . ' | Line: ' . $e->getLine());
            throw new Exception($e->getMessage());

            return false;
        }
    }

    public static function getBankAccountIdByCurrencyId($currency_id, $type)
    {
        return Bank::where($type, true)
            ->first()
            ->accounts
            ->where('account_currency', $currency_id)
            ->first()
            ->id;
    }

    public static function getTransactionMethods()
    {
        return TransactionMethod::where('status', 'active')->get()
            ->sortByDesc(function ($method) {
                return $method->name === 'SWIFT';
            })
            ->values();
    }

    public static function getPaymentTypeById($id)
    {
        if (!$id) {
            return null;
        }

        return DropdownConfiguration::where('type', 'PAYMENT_TYPE')->where('id', $id)->first();
    }

    public static function notifyComplianceTeamNeroData($trn_request)
    {
        if (Common::isModuleEnabled('NeroData')) {
            $trnTransaction = TRRequest::find($trn_request->id);
            if ($trnTransaction->compliance_status == 'MODERATION') {
                $neroDataHelper = new NeroDataHelper();
                $portalTransactionsUrl = $neroDataHelper->getPortalTransactionsUrl();
                $neroDataTransactionId = $neroDataHelper->getNeroDataTransactionIdByTrId($trn_request->id);
                EmailNotification::notifyComplianceTeamForAMLTransaction($trnTransaction->id, $neroDataTransactionId, $portalTransactionsUrl);
            }
        }
    }

    public static function generateOpenpaydAccountIBAN($accountId)
    {
        $account = Account::find($accountId);
        $currency = $account->currency;

        $client = ClientHelper::getClientByAccountId($account->id);
        $exists = VirtualBankAccount::where('client_id', $client->id)->where('currency', $currency->id)->first();

        if ($exists) {
            return ['success' => true];
        }

        $openpaydApis = new OpenpaydApis();
        $openpayd_client = $openpaydApis->storeOpenPaydClient($client);

        Log::info("\n\n\n");
        Log::info('------------------------------------------------');
        Log::info('storeOpenPaydClient function response: ' . print_r($openpayd_client, true));
        Log::info('------------------------------------------------');

        if ($openpayd_client && empty($openpayd_client->errorCode)) {
            $bank_account = $openpaydApis->storeOpenPaydAccount($account, $openpayd_client);

            Log::info("\n\n\n");
            Log::info('------------------------------------------------');
            Log::info('storeOpenPaydAccount function response: ' . print_r($bank_account, true));
            Log::info('------------------------------------------------');

            if (!empty($bank_account->errorCode)) {
                Log::error('Error creating OpenPayd Bank Account for Client ID: ' . $client->id . ' | Account ID: ' . $account->id);
                Log::error('OpenPayd Error: ' . $bank_account->errorCode);

                if ($bank_account->errorCode == "ACCOUNT_ALREADY_EXISTS") {
                    return ['success' => true];
                }

                return ['success' => false, 'message' => 'OpenPayd Error: ' . $bank_account->errorCode];
            }

            $account->virtual_bank_account_id = $bank_account->id;
            $account->save();

            return ['success' => true];
        } else if (!empty($openpayd_client->errorCode)) {
            Log::error('Error creating OpenPayd Client for Client ID: ' . $client->id);
            Log::error('OpenPayd Error: ' . $openpayd_client->errorCode);

            return ['success' => false, 'message' => 'OpenPayd Error: ' . $openpayd_client->errorCode];
        }
    }

    public static function generateLorumAccountIBAN($accountId)
    {
        $account = Account::find($accountId);
        $currency = $account->currency;

        $client = ClientHelper::getClientByAccountId($account->id);
        $exists = VirtualBankAccount::where('client_id', $client->id)->where('currency', $currency->id)->first();

        if ($exists) {
            return ['success' => true];
        }

        $lorumApis = new LorumApis();
        $lorum_client = $lorumApis->storeLorumdClient($client, $account);

        Log::info("\n\n\n");
        Log::info('------------------------------------------------');
        Log::info('storeLorumClient function response: ' . print_r($lorum_client, true));
        Log::info('------------------------------------------------');

        if ($lorum_client && !$lorum_client->error) {

            if (!empty($lorum_client) && $lorum_client->status !== "ACTIVE") {
                return ['success' => false, 'message' => 'Lorum Client created with Status: ' . $lorum_client->status];
            }

            $bank_account = $lorumApis->storeLorumAccount($account, $lorum_client);

            Log::info("\n\n\n");
            Log::info('------------------------------------------------');
            Log::info('storeLorumAccount function response: ' . print_r($bank_account, true));
            Log::info('------------------------------------------------');

            if (!empty($bank_account->errorCode) || !empty($bank_account->error)) {

                if ($bank_account->error === true && $bank_account->status_code === 404) {
                    $messages = !empty($bank_account->message) ? $bank_account->message : 'The IBAN creation request was initiated, Refresh after some time to see the IBAN details.';
                } else {
                    $messages = !empty($bank_account->message) ? $bank_account->message : 'Something went wrong, please try again later.';
                }

                return ['success' => false, 'message' => $messages];
            }

            $account->virtual_bank_account_id = $bank_account->id;
            $account->save();

            // Get the Lorum Account details and save the IBAN and other details in our database.
            $lorum_controller = new LorumController();
            $lorum_controller->getLorumAccountDetails($bank_account->virtual_bank_account_id);

            return ['success' => true];
        } else if (!empty($lorum_client->message)) {
            return ['success' => false, 'message' => 'Lorum Error: ' . $lorum_client->message];
        }
    }

    public static function generateZenusAccountIBAN($accountId)
    {
        $account = Account::find($accountId);
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

        $zenusApis = new ZenusApis();
        $zenus_client = $zenusApis->storeZenusClient($client, $account);

        if ($zenus_client && !$zenus_client->error) {

            if (!empty($zenus_client) && $zenus_client->status !== "ACTIVE") {
                $messages = 'Zenus Client created with Status: ' . $zenus_client->status . '. Please wait for the onboarding process to complete.';
                return redirect(url()->previous())->with('warning', $messages);
            }

            $bank_account = $zenusApis->storeZenusAccount($account, $zenus_client);
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

            $account->virtual_bank_account_id = $bank_account->id;
            $account->save();

            $userWithAPIRole = User::whereHas('roles', function ($query) {
                    $query->where('name', 'API');
                })
                    ->whereHas('linkClients', function ($query) use ($client) {
                        $query->where('client_id', $client->id);
                    })
                    ->first();

            if ($userWithAPIRole) {
                self::sendWebhookNotification($account, $userWithAPIRole, 'account', 'The named account has been created successfully.', 'Created');
            }

            return ['success' => true];
        } else if (!empty($zenus_client->message)) {
            return ['success' => false, 'message' => 'Zenus Error: ' . $zenus_client->message];
        }
    }
    public static function isAllowedApprover($trn_id, $modelClass)
    {
        $model = app("\\App\\Models\\$modelClass");
        $creator = $model::find($trn_id)->created_by;

        if ($creator == Auth::user()->id) {
            return false;
        }

        return true;
    }

    public static function isApproverReviewer($trn_id, $type)
    {
        if ($type == 'AccountTransaction') {
            $transaction = AccountTransaction::find($trn_id);
            $transaction = $transaction->requestedTrn;
        } else if ($type == 'TRRequest') {
            $transaction = TRRequest::find($trn_id);
        }

        if (!empty($transaction)) {
            if ($transaction->transactionDetails && $transaction->transactionDetails->reviewer) {
                if ($transaction->transactionDetails->reviewer->id == Auth::user()->id) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function neroDataComplianceStatus($status)
    {
        //for now we allow the transaction to be approved even if neroData status is not completed,we will uncomment this code in the future
        // if (($status == "MODERATION" || $status == "CANCELLED" || $status == "ASSIGNED") && self::isModuleEnabled('NeroData')) {
        //     throw new Exception("Something went wrong. Please try again later.");
        // }
    }

    public static function toMajorAmount($amount)
    {
        return $amount / 100;
    }

    public static function toMinorAmount($amount)
    {
        return $amount * 100;
    }

    public static function closeRestoreDirector($user_email, $status = "Closed")
    {
        $user = User::where('email', $user_email)->first();
        if ($user) {
            $user->locked = $status == "Closed" ? true : false;
            $user->save();
        }
    }

    public static function closeRestoreLinkedUsers($client, $status = "Closed")
    {
        // Lock linked users that have no other active client links
        foreach ($client->users as $clientUser) {
            $hasOtherLinks = ClientUser::where('user_id', $clientUser->user_id)
                ->where('client_id', '!=', $client->id)
                ->whereHas('client', function ($query) {
                    $query->whereNotIn('status', ['Closed', 'Rejected']);
                })
                ->exists();

            if (!$hasOtherLinks) {
                $user = User::find($clientUser->user_id);
                if ($user) {
                    $user->locked = $status == "Closed" ? true : false;
                    $user->save();
                }
            }
        }
    }

    public static function getClientPassportUrl($client_id, $client_type)
    {
        $kyc = ClientKYC::where('client_id', $client_id)
            ->whereNull('director_id')
            ->first();

        $passport = $kyc->passport_copy_url;

        if (empty($kyc->passport_copy_url)) {

            $file_config = ClientFileConfig::where('identifier', 'passport_copy')
                ->where('type', $client_type)->first();

            if ($client_type === "company") {
                $passport = ClientCompanyFile::where('client_id', $client_id,)
                    ->where('file_id', $file_config->id)->first();
            } else {
                $passport = ClientIndividualFile::where('client_id', $client_id,)
                    ->where('file_id', $file_config->id)->first();
            }

            $passport = $passport?->url;
        }
        return $passport;
    }

    /**
     * Fetches a virtual bank account by its unique ID.
     * * @param int|string $virtual_bank_account_id
     * @return VirtualBankAccount|null
     */
    public static function findVirtualBankAccountById($virtual_bank_account_id)
    {
        if (!$virtual_bank_account_id) {
            return null;
        }

        return VirtualBankAccount::where('virtual_bank_account_id', $virtual_bank_account_id)->first();
    }

    public static function getPaymentMethodByCurrency($currencyShort, $currencyProvider)
    {
        $paymentMethodsMap = [
            'zenus' => [
                'AED' => ["IBAN"],
                'USD' => ["ACH", "FED_WIRE", "CROSS_BORDER_SWIFT"]
            ],
            'lorum' => [
                'AED' => ["IBAN"],
                'USD' => ["WIRE"]
            ],
            'openpayd' => [
                'EUR' => ["SEPA", "SEPA_INSTANT", "TARGET2"],
                'GBP' => ["FASTER_PAYMENTS", "CHAPS"]
            ]
        ];

        // Safely get payment methods array
        $paymentMethodsArray = $paymentMethodsMap[$currencyProvider][$currencyShort] ?? [];

        return TransactionMethod::where(function ($query) use ($paymentMethodsArray) {
            if (!empty($paymentMethodsArray)) {
                $query->whereIn('name', $paymentMethodsArray);
            }
        })
            ->orWhere('is_default', true)
            ->get();
    }

    public static function generateResponse($request, $modelClass, $data, $message, $includeIdempotency = true)
    {
        $response_data = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];

        if (!$includeIdempotency) {
            return $response_data;
        }

        $idempotencyResponse = new IdempotencyKey();

        $idempotencyResponse->idempotency_key = $request->header('Idempotency-Key');
        $idempotencyResponse->model_class = $modelClass;
        $idempotencyResponse->response_data = json_encode($response_data);

        $idempotencyResponse->save();

        return $response_data;
    }
}
