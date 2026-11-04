<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Currency;
use App\Helpers\FileFirewall;
use App\Helpers\S3Helper;
use App\Models\PerCurrencyMaintenanceFee;
use App\Rules\SupportingDocumentsRule;
use Exception;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:currency list', ['only' => ['index', 'addCurrency', 'storeCurrency', 'editCurrency', 'updateCurrency', 'deleteCurrency']]);
    }

    public function index()
    {
        $currencies = Currency::where("is_crypto", false)->get();

        return view("finance-management.currency.list", compact("currencies"));
    }

    public function addCurrency()
    {
        return view("finance-management.currency.new");
    }

    public function storeCurrency(Request $request)
    {
        $this->validate($request, [
            'short' => 'required|unique:currencies,short',
            'name' => 'required',
            'individual_fee' => 'required:double',
            'company_fee' => 'required:double',
            'onboarding_fee_individual' => 'required:double',
            'onboarding_fee_company' => 'required:double',
            'logo' => ['required', new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg']
        ]);

        try {
            $currency = new Currency();

            $currency->short = $request->get("short");
            $currency->name = $request->get("name");
            $currency->request_auto_approval = ($request->get("request_auto_approval") ? 1 : 0);
            $currency->hide_for_client = ($request->get("hide_for_client") ? 1 : 0);
            $currency->onboarding_fee_individual = $request->get("onboarding_fee_individual");
            $currency->onboarding_fee_company = $request->get("onboarding_fee_company");
            $currency->is_active = ($request->get("is_active")) ? 1 : 0;
            //$currency->is_openpayd = ($request->get("is_openpayd")) ? 1 : 0;
            //$currency->is_lorum = ($request->get("is_lorum")) ? 1 : 0;       
            $currency->virtual_iban_provider = $request->get("virtual_iban_provider");
            
            $currency->transaction_fee_rate = $request->get("transaction_fee_rate");
            $currency->transaction_fee_threshold = $request->get("transaction_fee_threshold");
            $currency->bank_fee_rate = $request->get("bank_fee_rate");
            $currency->bank_fee_threshold = $request->get("bank_fee_threshold");

            $currency->is_crypto = 0;

            if ($request->hasFile('logo')) {

                // validate file
                if (!FileFirewall::isAllowedType($request->file('logo'))) {
                    return FileFirewall::logBadFileException($request->file('logo'));
                }

                if ($currency->logo) {
                    S3Helper::fileDelete($currency->logo);
                }

                $currency->logo = S3Helper::fileUpload($request->file('logo'), "crypto-logos/" . $request->get("short"));
            }

            $currency->save();

            $maintenanceFees = new PerCurrencyMaintenanceFee();

            $maintenanceFees->currency_id = $currency->id;
            $maintenanceFees->individual_fee = $request->get('individual_fee');
            $maintenanceFees->company_fee = $request->get('company_fee');

            $maintenanceFees->save();
            return redirect("/finance-management/currencies")->with("success", "Currency added successfully");
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__, $request->all());

            return redirect("/finance-management/currencies")->with("error", "Something went wrong, please try again later");
        }
    }

    public function editCurrency($id)
    {
        $currency = Currency::find($id);

        return view("finance-management.currency.edit", compact('currency'));
    }

    public function updateCurrency(Request $request)
    {
        $this->validate($request, [
            'short' => 'required',
            'name' => 'required',
            'individual_fee' => 'required:double',
            'company_fee' => 'required:double',
            'onboarding_fee_individual' => 'required:double',
            'onboarding_fee_company' => 'required:double',
            'logo' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg']
        ]);

        $currency = Currency::find($request->get("id"));

        try {
            $currency->short = $request->get("short");
            $currency->name = $request->get("name");
            $currency->request_auto_approval = ($request->get("request_auto_approval")) ? 1 : 0;
            $currency->hide_for_client = ($request->get("hide_for_client")) ? 1 : 0;
            $currency->onboarding_fee_individual = $request->get("onboarding_fee_individual");
            $currency->onboarding_fee_company = $request->get("onboarding_fee_company");
            $currency->is_active = ($request->get("is_active")) ? 1 : 0;

            $currency->transaction_fee_rate = $request->get("transaction_fee_rate");
            $currency->transaction_fee_threshold = $request->get("transaction_fee_threshold");
            $currency->bank_fee_rate = $request->get("bank_fee_rate");
            $currency->bank_fee_threshold = $request->get("bank_fee_threshold");

            $currency->virtual_iban_provider = $request->get("virtual_iban_provider");

            if (!$currency->logo) {
                $this->validate($request, [
                    'logo' => ['required', new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg']
                ]);
            }

            if ($request->hasFile('logo')) {

                // validate file
                if (!FileFirewall::isAllowedType($request->file('logo'))) {
                    return FileFirewall::logBadFileException($request->file('logo'));
                }

                if ($currency->logo) {
                    S3Helper::fileDelete($currency->logo);
                }

                $currency->logo = S3Helper::fileUpload($request->file('logo'), "crypto-logos/" . $request->get("short"));
            }

            $currency->save();

            if ($currency->maintenance) {
                $maintenanceFees = PerCurrencyMaintenanceFee::find($currency->maintenance->id);
                $maintenanceFees->currency_id = $currency->id;
                $maintenanceFees->individual_fee = $request->get('individual_fee');
                $maintenanceFees->company_fee = $request->get('company_fee');
            } else {
                $maintenanceFees = new PerCurrencyMaintenanceFee();

                $maintenanceFees->currency_id = $currency->id;
                $maintenanceFees->individual_fee = $request->get('individual_fee');
                $maintenanceFees->company_fee = $request->get('company_fee');
            }

            $maintenanceFees->save();

            return redirect("/finance-management/currencies")->with("success", "Currency updated successfully");
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__, $request->all());

            return redirect("/finance-management/currencies")->with("error", "Something went wrong, please try again later");
        }
    }

    public function deleteCurrency(Request $request)
    {
        $currency = Currency::find($request->get("row_id"));

        // check if currency is not attached to any transaction
        $isTransactionExists = AccountTransaction::where("currency_id", $currency->id)->first();

        // check if currency is not attached to any account
        $isAccountExists = Account::where("account_currency", $currency->id)->first();

        if ($isTransactionExists || $isAccountExists) {
            return redirect("/finance-management/currencies")->with("error", "Currency can't be deleted, its attached to some transactions and or accounts");
        } else {
            if ($currency->logo) {
                S3Helper::fileDelete($currency->logo);
            }

            $currency->delete();
            return redirect("/finance-management/currencies")->with("success", "Currency deleted successfully");
        }
    }
}
