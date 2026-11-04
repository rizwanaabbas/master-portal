<?php

namespace App\Models;

use App\Helpers\ChangeLogger;
use App\Helpers\Common;
use App\Helpers\NeroDataHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Casts\CleanHtml;
use App\Models\Client;
use App\Models\Account;
use App\Models\Country;
use App\Models\ClientBeneficiary;
use App\Models\BitpaceBeneficiary;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class TRRequest extends Model
{
    use HasFactory;

    public $timestamps = true;
    protected $table = "account_transaction_requests";

    protected $casts = [
        'account_id' => CleanHtml::class,
        'amount' => CleanHtml::class,
        'transaction_date' => CleanHtml::class,
        'remarks' => CleanHtml::class,
    ];
    protected static function boot()
    {
        parent::boot();
        //$client = $this->getClientByAccountId($trRequest->account_id);
        if (!Common::isModuleEnabled('NeroData')) {
            return; // do nothing when module is disabled
        }
        static::created(function ($trRequest) {
            //$account = Account::where('id',$trRequest->account_id)->first();
            //$client_id = $account->client_id;
            //$client  = Client::where('id',$client_id)->first();
            $filters = ['Buy', 'Sell', 'Deposit', 'Withdraw'];
            if ($trRequest->account->client_id !== null && !in_array($trRequest->transaction_nature, $filters)) {
                $metaData = self::getClientCountryBusinessData($trRequest->account_id);
                $beneficiary = ClientBeneficiary::find($trRequest->beneficiary_id);
                $payload = [
                    "transaction" => [
                        "transactionId" => (string) $trRequest->id,
                        "transactionDate" => now()->format('Y-m-d\TH:i:s.vO'),
                        "transactionMetadata" => [
                            "transactionAccountId" => $trRequest->account_id,
                            "transactionBeneficiaryId" => $trRequest->beneficiary_id,
                            "transactionCryptoSystem" => $trRequest->crypto_system,
                            "transactionNature" => $trRequest->transaction_type,
                            "transactionToWalletAddress" => $trRequest->to_wallet_address,
                            "clientNationalityCountry" => !empty($metaData['nationality']) ? $metaData['nationality']->title : null,
                            "clientNationalityCountryRiskScore" => !empty($metaData['nationality']) ? $metaData['nationality']->final_score : null,
                            "clientNationalityCountryRiskScoreValue" => !empty($metaData['nationality']) ? (float) $metaData['nationality']->final_score_value : null,
                            "clientResidenceCountry" => !empty($metaData['residence']) ? $metaData['residence']->title : null,
                            "clientResidenceCountryRiskScore" => !empty($metaData['residence']) ? $metaData['residence']->final_score : null,
                            "clientResidenceCountryRiskScoreValue" => !empty($metaData['residence']) ? (float) $metaData['residence']->final_score_value : null,
                            "clientBusinessActivity" => !empty($metaData['activity']) ? $metaData['activity']->title : null,
                            "clientBusinessActivityRiskScore" => !empty($metaData['activity']) ? $metaData['activity']->final_score : null,
                            "clientBusinessActivityRiskScoreValue" => !empty($metaData['activity']) ? (float) $metaData['activity']->final_score_value : null,
                        ],
                        "originalTransactionId" =>  (string) $trRequest->id,
                        "transactionAmount" => (string) $trRequest->amount,
                        "transactionCurrency" => $trRequest->currency->short ?? "USD",
                        "transactionType" => $trRequest->transaction_nature,
                        "customerId" => $metaData['client_id'],
                        "customerFirstName" => $metaData['client_name']
                        //"customerId" => $client->id,
                    ],
                    "container" => [
                        "name" => "Client Transactions"
                    ]
                ];

                $neroDataHelper = new NeroDataHelper();
                $neroDataHelper->sendTransaction($payload, $beneficiary);
            }
        });
    }

    protected static function booted()
    {
        static::addGlobalScope('not_deleted', function (Builder $builder) {
            $builder->where($builder->getModel()->qualifyColumn('is_deleted'), false);
        });
    }
    // public static function getClientByAccountId($account_id) {
    //     $account = Account::where('id',$account_id)->first();
    //     $client_id = $account->client_id;
    //     $client  = Client::where('id',$client_id)->first();
    //     return $client;
    // }

    public static function getClientCountryBusinessData($account_id)
    {
        $data = [];
        $account = Account::where('id', $account_id)->first();
        $client_id = $account->client_id;
        $client  = Client::where('id', $client_id)->first();
        $business_activity = '';
        $activity = '';
        if ($client->type == 'company') {
            $business_activity = $client->company->nature_of_business;
            $nationality_id = $client->company->contact->nationality_id;
            $residence_country_id = $client->company->contact->residence_country_id;
            $activity = self::getBusinessActivityRiskScore($business_activity);
        } else {
            //dd($client->individual);
            $nationality_id = $client->individual->nationality_id;
            $residence_country_id = $client->individual->residence_country_id;
        }
        $nationality = self::getCountryRiskScore($nationality_id);
        $residence = self::getCountryRiskScore($residence_country_id);
        $data['client_id'] = $client_id;
        $data['client_name'] = $client->name;
        $data['nationality'] = $nationality;
        $data['residence'] = $residence;
        $data['activity'] = $activity;

        return $data;
    }

    public static function getCountryRiskScore($countryId)
    {

        $country = Country::where('id', $countryId)->first();
        if (!empty($country)) {
            $riskScore = DB::table('risk_score_matrix')
                ->where('title', $country->name)
                ->where('matrix_type', 'countries')
                ->select('title', 'final_score', 'final_score_value')
                ->first();
            return $riskScore;
        }
        return '';
    }

    public static function getBusinessActivityRiskScore($activity)
    {
        $riskScore = DB::table('risk_score_matrix')
            ->where('title', $activity)
            ->where('matrix_type', 'industries')
            ->select('title', 'final_score', 'final_score_value')
            ->first();
        return $riskScore;
    }


    // public static function boot()
    // {
    //     parent::boot();

    //     self::updating(function ($model) {

    //         // track changes only after approval, no need to track changes before approval
    //         // if ($model->status == "Approved") {
    //         // log changes for approval if user can't make the change
    //         if (!Common::canUserMakeChange()) {
    //             ChangeLogger::log($model, "Transaction Request updated", url()->previous());
    //             return false;
    //         }
    //         // }
    //     });
    // }

    public function docs()
    {
        return $this->hasMany(TRRequestDoc::class, 'transaction_id', 'id');
    }

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    }

    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }

    public function receiver()
    {
        return $this->hasOne(Account::class, 'account_number', 'receiver_account_number');
    }

    public function approver()
    {
        return $this->hasOne(User::class, 'id', 'approved_by');
    }

    public function displayApproverName(): ?string
    {
        if (strcasecmp((string) $this->status, 'Approved') !== 0) {
            return null;
        }

        if (AccountTransaction::usesAutomatedApproval($this->crypto_system)) {
            return 'Automated Approval';
        }

        if ($this->approved_by !== null && (int) $this->approved_by === 0) {
            return 'Automated Approval';
        }

        return $this->approver->name ?? null;
    }

    public function beneficiary()
    {
        return $this->hasOne(ClientBeneficiary::class, 'id', 'beneficiary_id');
    }

    public function bitpaceBeneficiary()
    {
        return $this->hasOne(BitpaceBeneficiary::class, 'tr_request_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class, 'tr_request_id');
    }

    public function linkedTrn()
    {
        return $this->hasOne(AccountTransaction::class, 'id', 'link_tr_id');
    }

    public function sanctions()
    {
        return $this->hasMany(Sanctions::class, 'case_id');
    }

    public function card()
    {
        return $this->hasOne(AssignedCard::class, 'mpin', 'receiver_account_number');
    }

    public function transactionDetails()
    {
        return $this->hasOne(TransactionDetails::class, 'trn_id', 'id');
    }

    public function method()
    {
        return $this->hasOne(TransactionMethod::class, 'id', 'transaction_method_id');
    }

    public function notes()
    {
        return $this->hasMany(AccountTransactionNote::class, 'transaction_request_id', 'id');
    }

    public function rejector()
    {
        return $this->hasOne(User::class, 'id', 'rejected_by');
    }

    public function senderDetails()
    {
        return $this->hasOne(SenderDetail::class, 'tr_request_id', 'id');
    }
}
