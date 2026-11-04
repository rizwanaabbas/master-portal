<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Models\Account;
use App\Helpers\ClientHelper;
use App\Helpers\IDORHelper;
use App\Helpers\FileFirewall;
use App\Helpers\S3Helper;
use App\Models\Client;
use App\Models\TRRequest;
use App\Models\TRRequestDoc;
use App\Models\Currency;
use App\Rules\SupportingDocumentsRule;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Helpers\GEDMerchantApis;
use App\Models\AccountTransaction;
use Session;


class GedTRRequestController extends Controller
{
    private $helper;
    public function __construct()
    {
        //$this->middleware('permission:add crypto transaction', ['only' => ['newAdminBuy', 'newAdminSell', 'newAdminWithdraw', 'adminBuy', 'adminSell', 'adminWithdraw']]);
        $this->helper = new GEDMerchantApis();
    }

    public function getAccountsByClientCurrency($client_ids, $currency_code) {
        
        $accounts = Account::join('currencies', 'accounts.account_currency', '=', 'currencies.id')
            ->where('currencies.short', $currency_code)
            ->whereIn('accounts.client_id', $client_ids)
            ->where('accounts.is_active', true)
            ->select('accounts.id', 'accounts.title', 'accounts.account_number')
            ->get();
            return $accounts;
    }

    public function newBuy()
    {
        
        $client_ids = Common::getClientIdsByUserId(Auth::user()->id);
        $cryptoCurrencies = Currency::where("is_active", true)->where("is_crypto", true)->where("short", 'GED')->select("id")->get();
        $cryptoAccounts = Account::whereIn("client_id", $client_ids)
            ->whereIn("account_currency", $cryptoCurrencies)
            ->where("is_active", true)
            ->select("id", "title", "account_number", "account_currency")
            ->get();
        
        $allowed_fiat_curreincies = $this->helper->getAllowedFiatCurrencies(true);
        
        $currencies = Currency::where("is_active", true)
            ->where("is_crypto", false);
            
            if($allowed_fiat_curreincies != '')
                $currencies->whereIn("short", $allowed_fiat_curreincies);
            
            $currencies->select("id")
            ->get();
        
            $accounts = Account::whereIn("client_id", $client_ids)
            ->whereIn("account_currency", $currencies)
            ->where("is_active", true)
            ->select("id", "title", "account_number")
            ->get();

        return view("client-portal.crypto-transaction-request.ged.new.buy", compact('accounts', 'cryptoAccounts'));
    }

    public function buy(Request $request)
    {
        $this->validate($request, [
            'debit_account_id' => 'required',
            'receiver_account_id' => 'required',
            'order_type' => 'required',
            'amount' => 'required'
        ]);

        try {
            // Check the Debit account_id
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("debit_account_id"));

            if ($belongsToClient) {

                // Check the Credit account_id
                $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("receiver_account_id"));

                if ($belongsToClient) {

                    $debit_account = Account::find($request->get("debit_account_id"));
                    $client = ClientHelper::getClientByAccountId($request->get("debit_account_id"));
                    $credit_account = Account::find($request->get("receiver_account_id"));
                    $currency = Currency::where('id', $credit_account->account_currency)->first();
                    $amount = $request->get("amount");
                    $wallet_address = $request->get("wallet_address");
                    //if address exist dont take from form input
                    $address = DB::table('crypto_addresses')->where('account_id',$request->get("receiver_account_id"))->orderBy('id','DESC')->first();
                    if(!empty($address->id)) {
                        $wallet_address = $address->crypto_address;
                    }
                    
                    $credit_account_number = $credit_account->account_number;

                    if (empty($wallet_address) ) {
                        echo json_encode(array('status'=>'DECLINED','type'=>'internal','message'=>'Plese provide wallet address.')); die();
                     }
                    // if account is not available alert it
                    if (!$credit_account || !$debit_account) {
                       echo json_encode(array('status'=>'DECLINED','type'=>'internal','message'=>'Account details not found.')); die();
                    }

                    // check if same account is selected for transaction
                    if ($credit_account->id == $debit_account->id) {
                        echo json_encode(array('status'=>'DECLINED','type'=>'internal','message'=>'Please choose different accounts')); die();
                    }

                    // check if debit account is having enough balance
                    $balance = Common::getAccountBalanceById($debit_account->id);
                    //$cryptio_price_quote = Session::get('cryptio_price_quote');
                    //$cryptio_price_quote = json_decode($cryptio_price_quote);
                    if ($balance <= 0) {
                       $message =  "The account " . $debit_account->title . "  don't have enough money., please deposite balance in your account first.";
                       echo json_encode(array('status'=>'DECLINED','type'=>'internal','message'=>$message)); die();
                    }

                    if ($currency) {
                        $this->validate($request, [
                            'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
                        ]);
                    } else {
                        echo json_encode(array('status'=>'DECLINED','type'=>'internal','message'=>'Currency not found')); die();
                    }
                   
                    // create client transaction
                   
                    if(!empty($debit_account->id) && !empty($credit_account->id)) {
                        
                      
                        $ged_customer = $this->helper->customer( $credit_account );
                        
                        if($ged_customer->code == 400) {
                            Log::info('Customer on boarding client: ' . $ged_customer->message );
                            echo json_encode(array('status'=>'DECLINED','type'=>'internal','message'=>'Could not find customer info.')); die();
                            
                        }

                        if($ged_customer->code == 200) {
                            $blockchain_account = $this->helper->getBlockchainAccount( $credit_account ,$request->get("wallet_address"), 'Address Created from TR request.');
                            if($blockchain_account->code != 200) {
                                Log::info('Customer Blockchain account: ' . $blockchain_account->message );
                                echo json_encode(array('status'=>'DECLINED','type'=>'internal','message'=>'Could not create blockchain account.')); die();
                            }
                        }
                     
                        $dt_trn = new TRRequest();
                        // create debit transaction
                        $dt_trn->account_id = $debit_account->id;
                        $dt_trn->currency_id = $debit_account->account_currency;
                        $dt_trn->transaction_type = "Debit";
                        $dt_trn->transaction_nature = "Buy";
                        $dt_trn->to_wallet_address = $request->get("wallet_address");
                        $dt_trn->transaction_date = Carbon::createFromFormat('d/m/Y', date('d/m/Y',time()))->format('Y-m-d H:i:s');
                        $dt_trn->crypto_amount = $request->get("amount");
                        $dt_trn->amount = 0;
                        $dt_trn->receiver_account_number =  $credit_account_number;
                        $dt_trn->remarks = $request->get("remarks");
                        $dt_trn->status = "Pending";
                        $dt_trn->crypto_system = "GED";
                        $dt_trn->created_by = Auth::user()->id;
                    
                        if($dt_trn->save()) {
                            
                            //do api call and post transaction 
                            //$this->postBuySellTransaction($dt_trn,$credit_account,$debit_account,$request->get("order_type"),$token,$cryptio_price_quote);
                            echo json_encode(array('status'=>'SUCCESS','tr_id'=>$dt_trn->id,'type'=>'internal','message'=>'Please verify OTP to complete request.'));
                            EmailNotification::RequestOTP($dt_trn);

                            //return redirect("client/crypto-transaction-requests/otp/" . $dt_trn->id);
                        }
                        //Common::uploadSupportingDocuments($request, $dt_trn, $client);
                        // generate OTP code and send it to user
                       
                    }
                    else {
                        echo json_encode(array('status'=>'DECLINED','type'=>'internal','message'=>'Price not available for selected currency.')); die();
                    }
                }
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
               // return back()->with('error', 'You are not authorized to make this transaction.');
               echo json_encode(array('status'=>'DECLINED','type'=>'internal','message'=>'You are not authorized to make this transaction.')); die();
            } else {
                //return back()->with('error', 'Something went wrong, please try again later.');
                echo json_encode(array('status'=>'DECLINED','type'=>'internal','message'=>'Something went wrong, please try again later.' .$e->getMessage() )); die();
            }
        }
    }


    public function newSell()
    {
        $client_ids = Common::getClientIdsByUserId(Auth::user()->id);
        $fiatCurrencies = Currency::where("is_active", true)
        ->where("is_crypto", false);
        
        $allowed_fiat_curreincies = $this->helper->getAllowedFiatCurrencies(true);
        if($allowed_fiat_curreincies != '')
            $fiatCurrencies->whereIn("short", $allowed_fiat_curreincies);
            $fiatCurrencies->select("id")
            ->get();
      
        
        $currencies = Currency::where("is_active", true)->where("is_crypto", true)->where("short", 'GED')->select("id")->get();
        $accounts = Account::whereIn("client_id", $client_ids)
            ->where("is_active", true)
            ->whereIn("account_currency", $currencies)
            ->select("id", "title", "account_number")
            ->get();
       
       
        $account_id = '';
        $active_address = $this->getAccountsWalletAddress($accounts);
        if(!empty($active_address))
            $account_id = $active_address->account_id;
        //print_r($active_address);
        return view("client-portal.crypto-transaction-request.ged.new.sell", compact('accounts','account_id'));
    }

    public function sell(Request $request)
    {
        // validate input data
          $this->validate($request, [
            'account_id' => 'required',
            'wallet_address' => 'required'
        ]);
        try {
            // Check the Credit account_id
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("account_id"));

            if ($belongsToClient) {

                    $client = ClientHelper::getClientByAccountId($request->get("account_id"));
                    $credit_account = Account::find($request->get("account_id"));
                    $currency = Currency::where('id', $credit_account->account_currency)->first();

                    // if account is not available alert it
                    if ( !$credit_account ) {
                       //echo json_encode(array('status'=>'DECLINED','type'=>'internal','message'=>'Account details not found.')); die();
                    }

                    if( !empty( $credit_account->id )) {
                        $wallet_address = $request->get("wallet_address");
                        $ged_customer = $this->helper->customer( $credit_account );
                        if($ged_customer->code == 200) {
                            $blockchain_account = $this->helper->getBlockchainAccount( $credit_account ,$wallet_address, 'New Account from Sell');
                            if($blockchain_account->code == 200) {
                                $this->helper->storeCryptoAddress($credit_account->id,$wallet_address);
                                
                                return redirect(url()->previous())->with("success", "GED Account created succesfully.");
                            } else {
                                return redirect(url()->previous())->with("error", $blockchain_account->message);
                            }
                        } else {
                            return redirect(url()->previous())->with("error", $ged_customer->message);
                        }
                    } else {
                        return redirect(url()->previous())->with("error", "Crypto account do not exist.");
                    }
                
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                 return back()->with('error', 'You are not authorized to make this transaction.');
             } else {
                 return back()->with('error', 'Something went wrong, please try again later.');
             }
        }
    }

    public function getAccountsWalletAddress($accounts) {
        $ids = array();
        if( !empty($accounts) ) {
            
            foreach ($accounts as $account) {
                $ids[] = $account->id;
            }
        }

        $address = DB::table('crypto_addresses')->whereIn('account_id',$ids)->orderBy('id','DESC')->first();
        if(!empty($address->id)) {
           return $address;
        }
        //return $ids;
    }

 

    
    public function getWalletAddress(Request $request ) {
        $IDOR = new IDORHelper();
        
        $account_id =  $request->get("account_id");
        
        $is_account_owner = $IDOR->isAccountBelongToUser(Auth::user(), $account_id);
        if(!$is_account_owner) {
             echo json_encode(array());
        }
        //$address = DB::table('crypto_addresses')->where('account_id',$account_id)->orderBy('id','DESC')->first();
        $address =   DB::table('accounts')
        ->select(
            'accounts.account_number',
            'accounts.title',
            'currencies.name',
            'currencies.short',
            'currencies.logo',
            'users.email',
            'crypto_addresses.crypto_address as wallet_address', 
            'crypto_addresses.id as crypto_address_id',
            'crypto_addresses.created_at',

        )
        ->leftJoin('crypto_addresses', 'crypto_addresses.account_id', '=', 'accounts.id')
        ->leftJoin('currencies', 'currencies.id', '=', 'accounts.account_currency')
        ->leftJoin('clients', 'accounts.client_id', '=', 'clients.id')
        ->leftJoin('client_users', 'client_users.client_id', '=', 'clients.id')
        ->leftJoin('users', 'client_users.user_id', '=', 'users.id')
        
        ->where('accounts.id',$account_id)
        ->first();
        if (!empty($address)) {
            $redemption = $this->helper->getRedemptionAddress ( $address->account_number );
            //print_r($redemption);
            if($redemption->code == 200) {
                $address->redemption_address = $redemption->response->redemption_address;
                $address->redemption_address_status = $redemption->response->redemption_address_status;
            }

            echo json_encode($address); die();
        }
        echo json_encode(array()); die();
    }
}
