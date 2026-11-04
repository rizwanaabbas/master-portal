<?php

namespace App\Helpers;

use App\Mail\BalanceCertificatePDFMail;
use App\Mail\ClientKYCRequest;
use App\Mail\ClientPaymentRequest;
use App\Mail\ClientRejectedMail;
use App\Mail\CompanyOnboardingPDFMail;
use App\Mail\IndividualOnboardingPDFMail;
use App\Mail\MaintenanceFee3MonthsPendingAlertForFinanceManager;
use App\Mail\MaintenanceFeeAlertForFinanceManager;
use App\Mail\NotifyBeneficiaryCreationMail;
use App\Mail\NotifyChairPersonForVostroBanksReport;
use App\Mail\NotifyClientCreation;
use App\Mail\NotifyClientForBeneficiaryApproval;
use App\Mail\NotifyClientForBeneficiaryRejection;
use App\Mail\NotifyClientForCardAssignment;
use App\Mail\NotifyClientforCardBlock;
use App\Mail\NotifyClientforCardPinChange;
use App\Mail\NotifyClientForTransactionApproval;
use App\Mail\NotifyClientForTransactionInitialReviewApproval;
use App\Mail\NotifyClientForTransactionRejection;
use App\Mail\NotifyClientforCardUnblockApproval;
use App\Mail\NotifyClientforCardUnblockRejection;
use App\Mail\NotifyComplianceTeamForAMLTransactions;
use App\Mail\NotifyComplianceTeamForBeneficiarySanctions;
use App\Mail\NotifyCreatorClientApproval;
use App\Mail\NotifyCreatorClientReject;
use App\Mail\NotifyDepositeRequestMail;
use App\Mail\NotifyDraftClientCreationMail;
use App\Mail\NotifyFinanceForPayinMail;
use App\Mail\NotifyInternalRequestMail;
use App\Mail\NotifyManagerNewAccount;
use App\Mail\NotifyModelChange;
use App\Mail\NotifyModelChangeReview;
use App\Mail\NotifyNewPassword;
use App\Mail\NotifyOperationManagersForDocsExpiry;
use App\Mail\NotifyServiceRequestToOperationsMail;
use App\Mail\NotifyTransactionApproval;
use App\Mail\NotifyTransactionCreation;
use App\Mail\NotifyTransactionReject;
use App\Mail\NotifyTransferRequestMail;

use App\Mail\NotifyCryptoRequestMail;
use App\Mail\NotifyFireblocksLowCustodyBalanceMail;
use App\Mail\NotifyManagerForInsufficientFund;
use App\Mail\NotifyManagerForInsufficientBitpaceFund;
use App\Mail\NotifyRequestApproval;
use App\Mail\NotifyRequestRejection;
use App\Mail\NotifyTopupRequestMail;
use App\Mail\ClientCardTransaction;
use App\Models\Account;
use App\Models\FireblocksTransaction;
use App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringAlertRecipientService;
use App\Mail\Send3dSecureCode;
use App\Mail\SendCardProvisioningCode;
use App\Mail\SendOTP;
use App\Mail\SendRequestOTP;
use App\Mail\SumsubKYCRequestEmail;
use App\Mail\WelcomeEmail;
use App\Mail\UserCredentialEmail;
use App\Models\Client;
use App\Models\ClientDirector;
use App\Models\OTPCode;
use App\Models\User;
use App\Models\KycRequest;
use App\Models\UserCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class EmailNotification
{
    protected static $ownCompany;
    protected static $shortName;
    protected static $chairPersonEmail;
    protected static $userName;

    public static function setOwnCompanyData()
    {
        $ownCompany = Common::getOwnCompanyData() ? Common::getOwnCompanyData() : null;
        self::$ownCompany = $ownCompany;
        self::$shortName = $ownCompany ? $ownCompany->short_name : '';
        self::$chairPersonEmail = $ownCompany ? $ownCompany->chairperson_email : '';
    }

    public static function setUserName()
    {
        self::$userName = Auth::user() ? Auth::user()->name : self::$shortName;
    }

    public static function sendWelcomeEmail($client)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Welcome to " . self::$shortName,
            "name" => self::$shortName

        ];

        // attach SSI if checked
        $attachments = null;
        // if ($client->send_ssi) {
        //     if ($client->type == "individual") {
        //         if ($client->individual->accounts && $client->individual->accounts->count() > 0) {
        //             foreach ($client->individual->accounts as $account) {
        //                 $short = Common::getCurrencyShort($account->account_currency);
        //                 if (in_array($short, ["USD", "EUR"])) {
        //                     $attachments[$short] = self::createSSIPDF($client, $account);
        //                 }
        //             }
        //         }
        //     } elseif ($client->company->accounts && $client->company->accounts->count() > 0) {
        //         foreach ($client->company->accounts as $account) {
        //             $short = Common::getCurrencyShort($account->account_currency);
        //             if (in_array($short, ["USD", "EUR"])) {
        //                 $attachments[$short] = self::createSSIPDF($client, $account);
        //             }
        //         }
        //     }
        // }

        if ($client->type == "individual") {
            Mail::to($client->individual->email_address)->queue(new WelcomeEmail($client, $sender, $attachments, self::$ownCompany));
        } else {
            Mail::to($client->company->contact->email_address)->queue(new WelcomeEmail($client, $sender, $attachments, self::$ownCompany));
        }
    }

    private static function createSSIPDF($client, $account)
    {
        $currency_code = strtolower(Common::getCurrencyShort($account->account_currency));
        return PDF::loadHTML(view("emails.client.ssi-" . $currency_code, compact('client', 'account')))->setPaper('a4');
    }

    public static function notifyUserCredentials($user, $password)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Welcome to " . self::$shortName . " - Credentials",
            "name" => self::$shortName

        ];

        // $user->plain = $password;

        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => $password,
        ];

        Mail::to($user->email)->queue(new UserCredentialEmail($data, $sender, self::$ownCompany));
    }

    public static function notifyCreatorClientApproval($client)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Client Account Approved",
            "name" => self::$shortName

        ];


        Mail::to($client->creator->email)->queue(new NotifyCreatorClientApproval($client, $sender, self::$userName, self::$ownCompany));
    }

    public static function notifyClientForCardUnblockApproval($client, $linkedCardId = 0)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Card Unblocked",
            "name" => self::$shortName

        ];

        Mail::to($client->users[0]->user->email)->queue(new NotifyClientforCardUnblockApproval($client, $linkedCardId, $sender, self::$ownCompany));
    }

    public static function notifyClientforCardUnblockRejection($client)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Card Unblock Request Incomplete",
            "name" => self::$shortName

        ];

        Mail::to($client->users[0]->user->email)->queue(new NotifyClientforCardUnblockRejection($client, $sender, self::$ownCompany));
    }

    public static function notifyClientForCardBlock($client, $linkedCardId = 0)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Card Blocked",
            "name" => self::$shortName

        ];

        Mail::to($client->users[0]->user->email)->queue(new NotifyClientforCardBlock($client, $linkedCardId, $sender, self::$ownCompany));
    }

    public static function notifyClientForCardPinChange($client)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Card Pin Changed",
            "name" => self::$shortName

        ];

        Mail::to($client->users[0]->user->email)->queue(new NotifyClientforCardPinChange($client, $sender, self::$ownCompany));
    }

    public static function notifyCreatorClientReject($client)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Client Account Incomplete",
            "name" => self::$shortName

        ];

        Mail::to($client->creator->email)->queue(new NotifyCreatorClientReject($client, $sender, self::$userName, self::$ownCompany));
    }

    public static function notifyTransactionApproval($transaction)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Transaction Approved",
            "name" => self::$shortName

        ];

        Mail::to($transaction->creator->email)->queue(new NotifyTransactionApproval($transaction, $sender, self::$userName, self::$ownCompany));
    }

    public static function notifyTransactionRejection($transaction)
    {
        self::setOwnCompanyData();
        $trn_nature = "Transaction";

        if ($transaction->transaction_nature == "Top-up") {
            $trn_nature = "Top-up";
        }

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => $trn_nature . " Rejected",
            "name" => self::$shortName

        ];

        Mail::to($transaction->creator->email)->queue(new NotifyTransactionReject($transaction, $sender, $trn_nature, self::$userName, self::$ownCompany));
    }

    public static function notifyTransactionCreation($transaction)
    {
        self::setOwnCompanyData();

        if (Auth::user()->hasRole("finance")) {
            $sender = [
                "email" => env("MAIL_FROM_ADDRESS"),
                "subject" => "Transaction - Pending Approval",
                "name" => self::$shortName

            ];

            if ($transaction->creator->manager) {
                Mail::to($transaction->creator->manager->email)->queue(new NotifyTransactionCreation($transaction, $sender, self::$userName, self::$ownCompany));
            }
        }
    }

    public static function maintenanceFeeAlertForFinanceManager($count, $clientsPendingFor3Months)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Maintenance Fee Alert - " . Carbon::now(),
            "name" => self::$shortName
        ];

        $managers = User::whereHas("roles", function ($q) {
            $q->whereIn("name", ["finance manager", "operations manager"]);
        })->get();

        foreach ($managers as $manager) {
            Mail::to($manager->email)->queue(new MaintenanceFeeAlertForFinanceManager($count, $sender, $manager, self::$ownCompany, $clientsPendingFor3Months));
        }
    }

    public static function maintenanceFee3MonthsPendingAlertForFinanceManager($clients)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Maintenance Fee Alert - 3 Months Pending Clients - " . Carbon::now(),
            "name" => self::$shortName
        ];

        $managers = User::whereHas("roles", function ($q) {
            $q->where("name", "finance manager");
        })->get();

        foreach ($managers as $manager) {
            Mail::to($manager->email)->queue(new MaintenanceFee3MonthsPendingAlertForFinanceManager($clients, $sender, $manager, self::$ownCompany));
        }
    }

    public static function notifyFinanceManagersForTRRequest($transaction, $type)
    {
        self::setOwnCompanyData();

        if ($transaction->transaction_nature == 'Top-up') {
            $needle = 'Top-up';
        } else {
            $needle = $transaction->transaction_nature . ' Transaction';
        }
        if ($transaction->crypto_amount > 0) {
            $needle  = "Crypto " . $needle;
        }
        $subject =  $needle . " Request - Pending Approval ";

        if ($transaction->status == 'Approved' && $transaction->transaction_nature == 'Deposit') {
            $cryptoSystem = strtolower((string) ($transaction->crypto_system ?? ''));
            if ($cryptoSystem === 'fireblocks') {
                if (!$transaction->relationLoaded('account')) {
                    $transaction->load('account.currency');
                }
                $accountNumber = (string) (optional($transaction->account)->account_number ?? '');
                $currencyShort = (string) (optional(optional($transaction->account)->currency)->short ?? '');
                $subject = trim('Crypto deposit credited');
            } else {
                $subject = "Crypto Deposit has been made by client";
            }
        }


        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => $subject,
            "name" => self::$shortName
        ];

        $managers = User::whereHas("roles", function ($q) {
            $q->whereIn("name", ["finance manager", "finance"]);
        })->get();

        foreach ($managers as $manager) {
            switch ($type) {

                case 'payin':
                    Mail::to($manager->email)->send(new NotifyFinanceForPayinMail($transaction, $sender, $manager, self::$userName, self::$ownCompany));
                    break;
                case 'deposit':
                    Mail::to($manager->email)->queue(new NotifyDepositeRequestMail($transaction, $sender, $manager, self::$userName, self::$ownCompany));
                    break;
                case 'internal':
                    Mail::to($manager->email)->queue(new NotifyInternalRequestMail($transaction, $sender, $manager, self::$userName, self::$ownCompany));
                    break;
                case 'transfer':
                    Mail::to($manager->email)->queue(new NotifyTransferRequestMail($transaction, $sender, $manager, self::$userName, self::$ownCompany));
                    break;
                case 'Buy':
                case 'Deposit':
                case 'Sell':
                case 'Swap':
                case 'Withdraw':
                    Mail::to($manager->email)->queue(new NotifyCryptoRequestMail($transaction, $sender, $manager, self::$userName, self::$ownCompany));
                    break;
                case 'Top-up':
                    Mail::to($manager->email)->queue(new NotifyTopupRequestMail($transaction, $sender, $manager, self::$userName, self::$ownCompany));
                    break;
            }
        }
    }

    public static function notifyClientCreation($client)
    {
        self::setOwnCompanyData();

        if (Auth::user()->manager_id > 0) {
            $sender = [
                "email" => env("MAIL_FROM_ADDRESS"),
                "subject" => "Client - Pending Approval",
                "name" => self::$shortName
            ];

            if ($client->creator->manager) {
                Mail::to($client->creator->manager->email)->queue(new NotifyClientCreation($client, $sender, self::$ownCompany));
            }
        }
    }

    public static function notifyDraftClientCreation($client)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Client - Pending Approval",
            "name" => self::$shortName
        ];

        // get operation managers
        $managers = User::whereHas("roles", function ($q) {
            $q->where("name", "operations manager");
        })->get();

        foreach ($managers as $manager) {
            Mail::to($manager->email)->queue(new NotifyDraftClientCreationMail($client, $sender, $manager, self::$ownCompany));
        }
    }

    public static function notifyNewPassword($user, $password)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "New Password Generated",
            "name" => self::$shortName

        ];

        Mail::to($user->email)->queue(new NotifyNewPassword($user, $sender, $password, self::$ownCompany));
    }

    public static function send3dSecureCode($client, $data)
    {
        self::setOwnCompanyData();

        $email = '';
        // $client_name = '';
        if ($client->type == 'individual') {
            $email = $client->individual->email_address;
        }

        if ($client->type == 'company') {
            $email = $client->company->contact->email_address;
        }

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Your 3d Secure code at " . $data->MerchantName,
            "name" => self::$shortName
        ];
        if (!empty($client->users)) {
            foreach ($client->users as $user) {
                $push_data['row_id'] = $user->user_id;
                //$push_data['clientname'] = $user->name;
                $push_data['url']  = '';

                $push_data['message'] = 'You 3d Secure code for card ending with ' . $data->LastFourDigit . ' is ' . $data->Otp . ' transaction initiated on ' .   $data->MerchantName;
                Common::createNotification($user->user_id, $push_data, $sender['subject']);
            }
        }
        if (!empty($email)) {

            //dd($client->users);



            Mail::to($email)->queue(new Send3dSecureCode($data, $sender, self::$ownCompany));
        }
    }
    public static function sendCardProvisioningCode($client, $data)
    {
        self::setOwnCompanyData();

        $email = '';
        if ($client->type == 'individual')
            $email = $client->individual->email_address;
        if ($client->type == 'company')
            $email = $client->company->contact->email_address;

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Your card provisioning code ",
            "name" => self::$shortName
        ];
        if (!empty($client->users)) {
            foreach ($client->users as $user) {
                $push_data['row_id'] = $user->user_id;
                //$push_data['clientname'] = $user->name;
                $push_data['url']  = '';

                $push_data['message'] = 'Your card ending with ' . $data->LastFourDigit . ' provisioning code is ' . $data->Otp;
                Common::createNotification($user->user_id, $push_data, $sender['subject']);
            }
        }

        if (!empty($email)) {
            Mail::to($email)->queue(new sendCardProvisioningCode($data, $sender, self::$ownCompany));
        }
    }

    public static function sendOTP()
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Login - OTP",
            "name" => self::$shortName
        ];

        // generate otp
        $data = [
            "otp" => rand(100000, 999999),
            "name" => Auth::user()->name
        ];

        // store otp in db
        UserCode::updateOrCreate(
            ['user_id' => auth()->user()->id],
            ['code' => $data['otp']]
        );

        // email otp
        Mail::to(Auth::user()->email)->queue(new SendOTP($data, $sender, self::$ownCompany));
    }

    public static function notifyModelChange($change)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Changes - Pending Approval",
            "name" => self::$shortName

        ];

        // if ($change->checker)
        //     Mail::to($change->checker->email)->queue(new NotifyModelChange($change, $sender));

        // if ($change->model)
        $managers = User::whereHas("roles", function ($q) {
            $q->where("name", "finance manager");
        })->get();

        foreach ($managers as $manager) {
            Mail::to($manager->email)->queue(new NotifyModelChange($change, $sender, self::$ownCompany));
        }
    }

    public static function notifyMaker($changes, $status)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Changes - " . $status,
            "name" => self::$shortName

        ];

        foreach ($changes as $user_id => $updates) {
            $user = \App\Models\User::find($user_id);
            Mail::to($user->email)->queue(new NotifyModelChangeReview($user, $updates, $sender, $status, Auth::user(), self::$ownCompany));
        }
    }

    public static function notifyManagerForNewAccount($model)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "New Client Currency Account",
            "name" => self::$shortName

        ];

        if (Auth::user()->manager_id > 0) {
            Mail::to(Auth::user()->manager->email)->queue(new NotifyManagerNewAccount($model, $sender, Auth::user(), self::$ownCompany));
        }
    }

    public static function sendPDF($client)
    {
        dd($client);
    }

    public static function sendIndividualOnboardingPDF($client)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Registration In Progress",
            "name" => self::$shortName

        ];

        # generate individual PDF

        $attachments['data'] = Common::getIndividualOnboardingPDFs($client);
        // $attachments['file'] = public_path("/assets/onboarding/cib-master-investment-agreement.pdf");

        Mail::to(Auth::user()->email)->queue(new IndividualOnboardingPDFMail($client, $sender, $attachments, self::$ownCompany));
    }

    public static function sendCompanyOnboardingPDF($client)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Registration In Progress",
            "name" => self::$shortName

        ];

        $attachments['data'] = Common::getCompanyOnboardingPDFs($client);
        // $attachments['file'] = public_path("/assets/onboarding/cib-master-investment-agreement.pdf");


        Mail::to(Auth::user()->email)->queue(new CompanyOnboardingPDFMail($client, $sender, $attachments, self::$ownCompany));
    }

    public static function notifyClientRejection($client)
    {
        self::setOwnCompanyData();

        $user = ClientHelper::getUserByClientId($client->id);

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Account Incomplete",
            "name" => self::$shortName

        ];

        Mail::to($user->email)->queue(new ClientRejectedMail($client, $sender, self::$ownCompany));
    }

    public static function notifyOperationManagersForBeneficiary($ben)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "New Beneficiary - Pending Approval",
            "name" => self::$shortName

        ];

        $managers = User::whereHas("roles", function ($q) {
            $q->where("name", "operations manager");
        })->get();

        foreach ($managers as $manager) {
            Mail::to($manager->email)->queue(new NotifyBeneficiaryCreationMail($ben, $sender, $manager, self::$userName, self::$ownCompany));
        }
    }

    public static function notifyBeneficiaryApproval($ben)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Beneficiary Approved",
            "name" => self::$shortName

        ];

        Mail::to($ben->user->email)->queue(new NotifyClientForBeneficiaryApproval($ben, $sender, self::$ownCompany));
    }

    public static function notifyBeneficiaryRejection($ben)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Beneficiary Rejected",
            "name" => self::$shortName

        ];

        Mail::to($ben->user->email)->queue(new NotifyClientForBeneficiaryRejection($ben, $sender, self::$ownCompany));
    }

    public static function notifyClientForTransactionApproval($trn)
    {
        self::setOwnCompanyData();
        $trn_nature = "Transaction";

        if ($trn->transaction_nature == "Top-up") {
            $trn_nature = "Top-up";
        } else {
            $trn_nature = $trn->transaction_nature . ' ' . $trn_nature;
        }

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => $trn_nature . " Approved",
            "name" => self::$shortName

        ];

        Mail::to($trn->creator->email)->queue(new NotifyClientForTransactionApproval($trn, $sender, $trn_nature, self::$ownCompany));
    }

    public static function notifyClientForTransactionInitialReviewApproval($trn)
    {
        self::setOwnCompanyData();

        $trn_nature = $trn->transaction_nature . ' Transaction';

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Initial Transaction Review Approved. Please send the fund.",
            "name" => self::$shortName
        ];

        Mail::to($trn->creator->email)->queue(new NotifyClientForTransactionInitialReviewApproval($trn, $sender, $trn_nature, self::$ownCompany));
    }

    public static function notifyClientForTransactionRejection($req)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Transaction Rejected",
            "name" => self::$shortName

        ];

        $baseUrl = env('REACT_APP_BASE_URL');

        Mail::to($req->creator->email)->queue(new NotifyClientForTransactionRejection($req, $sender, self::$ownCompany, $baseUrl));
    }

    public static function RequestOTP($request, $type = 'TRANSACTION_REQUEST')
    {
        self::setOwnCompanyData();

        if ($type == 'SERVICE_REQUEST') {
            $subjectNeedle = 'Service Request';
        } else if ($request->transaction_nature == 'Top-up') {
            $subjectNeedle = 'Top-up Request';
        } else {
            $subjectNeedle = 'Transaction';
        }

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "New " . $subjectNeedle . " - OTP",
            "name" => self::$shortName
        ];

        // generate otp
        $data = [
            "otp" => rand(100000, 999999),
            "name" => Auth::user()->name,
            "message_needle" => $subjectNeedle
        ];

        // store otp in db
        $otp = new OTPCode();

        $otp->user_id = Auth::user()->id;
        $otp->row_id = $request->id;
        $otp->type = $type;
        $otp->otp = $data['otp'];

        $otp->save();

        // email otp
        Mail::to(Auth::user()->email)->queue(new SendRequestOTP($data, $sender, self::$ownCompany));
    }

    public static function notifyOperationManagersForDocsExpiry($clientsData)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Documents Expiry Alert",
            "name" => self::$shortName

        ];

        $managers = User::whereHas("roles", function ($q) {
            $q->where("name", "operations manager");
        })->get();

        foreach ($managers as $manager) {
            Mail::to($manager->email)->queue(new NotifyOperationManagersForDocsExpiry($clientsData, $sender, $manager, self::$ownCompany));
            echo ".";
        }
    }

    public static function notifyRequestApproval($request, $account = null)
    {
        self::setOwnCompanyData();

        $typeMapping = ['APPLY_CARD' => 'New Card', 'APPLY_ACCOUNT' => 'New Account', 'APPLY_CRYPTO' => 'New Crypto Account', 'CLOSE_ACCOUNT' => 'Close Account', 'BALANCE_CERTIFICATE' => 'Balance Certificate'];
        $requestType = $typeMapping[$request->type];

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => $requestType . " Request Approved",
            "name" => self::$shortName

        ];

        Mail::to($request->maker->email)->queue(new NotifyRequestApproval($request, $sender, $requestType, $account, self::$userName, self::$ownCompany));
    }

    public static function notifyRequestRejection($request)
    {
        self::setOwnCompanyData();

        $typeMapping = ['APPLY_CARD' => 'New Card', 'APPLY_ACCOUNT' => 'New Account', 'APPLY_CRYPTO' => 'New Crypto Account', 'CLOSE_ACCOUNT' => 'Close Account', 'BALANCE_CERTIFICATE' => 'Balance Certificate'];
        $requestType = $typeMapping[$request->type];

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => $requestType . " Request Declined",
            "name" => self::$shortName

        ];

        Mail::to($request->maker->email)->queue(new NotifyRequestRejection($request, $sender, $requestType, self::$userName, self::$ownCompany));
    }

    public static function sendBalanceCertificatePDF($request)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Balance Certificate",
            "name" => self::$shortName
        ];

        $attachments = Common::getBalanceCertificatePDF($request);
        $balance_certificate_url = S3Helper::fileUpload(null, 'balance-certificate/', $attachments, 'pdf');

        Mail::to($request->maker->email)->queue(new BalanceCertificatePDFMail($request, $sender, 'Balance Certificate', $balance_certificate_url, self::$userName, self::$ownCompany));
    }

    public static function notifyClientForCardAssignment($links)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Card Assigned",
            "name" => self::$shortName
        ];

        Mail::to($links[0]->owner->users[0]->user->email)->queue(new NotifyClientForCardAssignment($links, $sender, self::$userName, self::$ownCompany));
    }

    public static function notifyManagerForInsufficientFund($transaction)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Insufficient Wallet Balance",
            "name" => self::$shortName
        ];

        $manager = User::whereHas("roles", function ($q) {
            $q->where("name", "finance manager");
        })->get();

        foreach ($manager as $manager) {
            Mail::to($manager->email)->queue(new NotifyManagerForInsufficientFund($transaction, $sender, self::$ownCompany));
        }
    }

    public static function notifyOperationsManagersForServiceRequest($service)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Service Request - Pending Approval",
            "name" => self::$shortName

        ];

        $managers = User::whereHas("roles", function ($q) {
            $q->where("name", "operations manager");
        })->get();


        foreach ($managers as $manager) {
            Mail::to($manager->email)->queue(new NotifyServiceRequestToOperationsMail($service, $sender, $manager, self::$userName, self::$ownCompany));
        }
    }

    public static function sendVostroBanksReportToChairPerson($report)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => self::$shortName . " - Corresponding Banks Report - " . \Carbon\Carbon::parse($report->report_date)->format('d M, Y'),
            "name" => self::$shortName

        ];

        Mail::to(self::$chairPersonEmail)->queue(new NotifyChairPersonForVostroBanksReport($report, $sender, self::$ownCompany));
    }

    public static function notifyComplianceTeamForSanctions($transaction)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Suspicious transaction",
            "name" => self::$shortName

        ];

        $complianceTeam = User::whereHas("roles", function ($q) {
            $q->where("name", "compliance");
        })->get();

        foreach ($complianceTeam as $compliance) {
            Mail::to($compliance->email)->queue(new NotifyComplianceTeamForBeneficiarySanctions($transaction, $sender, self::$ownCompany));
        }
    }
    public static function notifyFinanceManagersForTRRequestResolving($transaction, $needle)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => $needle,
            "name" => self::$shortName

        ];

        $managers = User::whereHas("roles", function ($q) {
            $q->whereIn("name", ["finance manager", "finance"]);
        })->get();

        foreach ($managers as $manager) {
            Mail::to($manager->email)->queue(new NotifyTransferRequestMail($transaction, $sender, $manager, self::$userName, self::$ownCompany));
        }
    }


    public static function notifyFinanceManagersForBitpaceLowBalance($transaction_request)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Insufficient Bitpace " . $transaction_request->account->currency->short . " Wallet Balance",
            "name" => self::$shortName

        ];

        $managers = User::whereHas("roles", function ($q) {
            $q->whereIn("name", ["finance manager", "finance"]);
        })->get();

        foreach ($managers as $manager) {
            Mail::to($manager->email)->queue(new NotifyManagerForInsufficientBitpaceFund($transaction_request, $sender, $manager, self::$ownCompany));
        }
    }

    /**
     * Client had ledger balance; Fireblocks custody rejected the withdrawal (insufficient vault funds).
     */
    public static function notifyOperationsAndFinanceForFireblocksLowCustody(
        FireblocksTransaction $record,
        array $context = []
    ): void {
        self::setOwnCompanyData();

        $account = null;
        if (!empty($context['account_id'])) {
            $account = Account::with('currency')->find($context['account_id']);
        }

        $assetId = (string) ($record->asset_id ?? '');
        $monitoringUrl = url(
            'fireblocks/transaction-monitoring/' . rawurlencode((string) $record->fireblocks_tx_id)
        );

        $sender = [
            'email'   => env('MAIL_FROM_ADDRESS'),
            'subject' => 'Insufficient Fireblocks custody — ' . ($assetId !== '' ? $assetId : 'withdrawal'),
            'name'    => self::$shortName,
        ];

        $transactionData = (object) [
            'fireblocks_tx_id'    => $record->fireblocks_tx_id,
            'asset_id'            => $record->asset_id,
            'amount'              => $record->amount,
            'vault_account_id'    => $record->source_vault_account_id,
            'destination_address' => $record->destination_address,
            'sub_status'          => $record->sub_status,
            'error_message'       => $record->error_message,
            'channel'             => $context['channel'] ?? 'Client Portal',
            'account'             => $account,
            'created_at'          => $record->created_at ?? now(),
            'monitoring_url'      => $monitoringUrl,
        ];

        foreach (MonitoringAlertRecipientService::usersForLowBalanceAlerts() as $recipient) {
            Mail::to($recipient->email)->queue(
                new NotifyFireblocksLowCustodyBalanceMail($transactionData, $sender, self::$ownCompany)
            );
        }
    }

    public static function notifyClientCardTransactionStatus($client, $txn_id = '', $text = '')
    {
        self::setOwnCompanyData();
        $email = '';
        if ($client->type == 'individual')
            $email = $client->individual->email_address;
        if ($client->type == 'company')
            $email = $client->company->contact->email_address;


        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Card Transaction",
            "name" => self::$shortName

        ];

        if (!empty($client->users)) {
            foreach ($client->users as $user) {
                $push_data['row_id'] = $user->user_id;
                //$push_data['clientname'] = $user->name;
                $push_data['url']  = '';
                $push_data['message'] = self::cleanText($text);
                Common::createNotification($user->user_id, $push_data, $sender['subject']);
                $send_notification = false;
            }
        }

        //dd($redirectUrl);
        if (!empty($email))
            Mail::to($email)->queue(new ClientCardTransaction($client, $sender, $txn_id, $text, self::$ownCompany, $send_notification));
    }
    public static function cleanText($text)
    {
        $clean = strip_tags($text);                     // remove HTML tags
        $clean = html_entity_decode($clean);            // decode any HTML entities
        $clean = preg_replace('/\s+/', ' ', $clean);    // remove multiple spaces/newlines
        $clean = trim($clean);                          // trim start/end spaces

        // Add a newline after every sentence
        $clean = preg_replace('/(\.)(\s)?/', "$1\n", $clean);

        // Ensure each line is properly trimmed
        $lines = array_map('trim', explode("\n", $clean));
        $clean = implode("\n", $lines);
        return $clean;
    }
    public static function notifyClientKYCRequest($client, $hash = '', $OnBoardingUrl = '')
    {
        self::setOwnCompanyData();

        //$user = ClientHelper::getUserByClientId($client->id);

        $email = '';
        if ($client->type == 'individual')
            $email = $client->individual->email_address;
        if ($client->type == 'company')
            $email = $client->company->contact->email_address;

        $kyc_request = KycRequest::where('hash', $hash)->first();

        if (!empty($hash)) {
            if (!empty($kyc_request->director_id))
                $email = $kyc_request->director->email;
        }

        $redirectUrl = '';

        if ($kyc_request) {
            $baseUrl = env('HAS_REACT_CLIENT_PORTAL_IN_REACT') ? env("REACT_APP_BASE_URL") : url('');
            $clientId = $client->id;
            $is_emoney = 0;
            if (!empty($OnBoardingUrl)) {
                $redirectUrl = $OnBoardingUrl;
                $is_emoney = 1;
            } elseif ($kyc_request->request_passport == 1) {
                $redirectUrl = "{$baseUrl}/client-passport-upload?hash={$hash}&client_id={$clientId}";
            } else if ($kyc_request->request_selfie == 1) {
                $redirectUrl = "{$baseUrl}/client-liveness-check?hash={$hash}&client_id={$clientId}";
            } else if ($kyc_request->request_signature  == 1) {
                $redirectUrl = "{$baseUrl}/client-signature-upload?hash={$hash}&client_id={$clientId}";
            }
        }

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "KYC Required",
            "name" => self::$shortName

        ];
        //dd($redirectUrl);
        if (!empty($email))
            Mail::to($email)->queue(new ClientKYCRequest($client, $sender, $redirectUrl, self::$ownCompany, $is_emoney));
    }

    public static function notifySumsubKYCRequest($client, $sumsubUrl, $reason = null, $director_id = null)
    {
        self::setOwnCompanyData();
        $email = '';

        if (!empty($client->parent_id)) {
            $parentClient = Client::find($client->parent_id);
            if ($parentClient) {
                if ($parentClient->type == 'individual') {
                    $email = $parentClient->individual->email_address;
                } elseif ($parentClient->type == 'company') {
                    $email = $parentClient->company->contact->email_address;
                }
            }
        } else {
            if ($client->type == 'individual') {
                $email = $client->individual->email_address;
            } elseif ($client->type == 'company') {
                $email = $client->company->contact->email_address;
            }
        }

        if (!empty($director_id)) {
            $director = ClientDirector::find($director_id);
            if ($director) {
                $email = $director->email;
            }
        }

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Action Required: Update Your Profile",
            "name" => self::$shortName
        ];

        if (!empty($email)) {
            Mail::to($email)->queue(new SumsubKYCRequestEmail($client, $sender, $sumsubUrl, self::$ownCompany, $reason));
        }
    }

    public static function notifyClientPaymentRequest($client, $link)
    {
        self::setOwnCompanyData();

        $user = ClientHelper::getUserByClientId($client->id);

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Payment Required",
            "name" => self::$shortName

        ];

        Mail::to($user->email)->queue(new ClientPaymentRequest($client, $sender, $link, self::$ownCompany));
    }

    public static function notifyComplianceTeamForAMLTransaction($transaction_id, $aml_transaction_id, $portalTransactionsUrl)
    {
        self::setOwnCompanyData();

        $sender = [
            "email" => env("MAIL_FROM_ADDRESS"),
            "subject" => "Suspicious transaction",
            "name" => self::$shortName

        ];

        $complianceTeam = User::whereHas("roles", function ($q) {
            $q->where("name", "compliance");
        })->get();
        foreach ($complianceTeam as $compliance) {
            Mail::to($compliance->email)->queue(new NotifyComplianceTeamForAMLTransactions($transaction_id, $aml_transaction_id, $sender, self::$ownCompany, $portalTransactionsUrl));
        }
    }
}
