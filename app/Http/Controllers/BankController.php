<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Models\Account;
use App\Models\Bank;
use App\Models\BanksSSI;
use App\Models\Currency;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mews\Purifier\Facades\Purifier;
use Stevebauman\Purify\Purify;
use Symfony\Component\Console\Input\Input;

class BankController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:bank list', ['only' => ['index', 'indexBankSSI']]);
        $this->middleware('permission:add bank', ['only' => ['addBank', 'storeBank', 'storeBankAccount', 'addBankSSI', 'storeBankSSI', 'deleteBankSSI']]);
        $this->middleware('permission:edit bank', ['only' => 'editBank', 'updateBank', 'editBankSSI', 'updateBankSSI']);
        $this->middleware('permission:delete bank', ['only' => 'deleteBank', 'deleteBankAccount']);
    }

    public function index()
    {
        $banks = Bank::all();

        return view("finance-management.banks.list", compact('banks'));
    }

    public function addBank()
    {
        return view("finance-management.banks.new");
    }

    public function storeBank(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'address' => 'required'
        ]);

        $bank = new Bank();
        $isServiceFeeBank = false;
        $isTrnFeeBank = false;

        if (array_key_exists('fee_type', $request->all())) {
            if ('service_fee_bank' == $request->get('fee_type')) {
                $isServiceFeeBank = true;
            } else {
                $isTrnFeeBank = true;
            }
        }

        if ($request->get("is_reserve_bank") == 1) {
            $existingReserveBank = Bank::where('is_reserve_bank', true)->first();
            if ($existingReserveBank) {
                return redirect("/finance-management/banks/add")
                    ->with('error', "Only one bank can be set as the Reserve Bank. The existing Reserve Bank is: " . $existingReserveBank->name);
            }
        }

        $bank->name = $request->get("name");
        $bank->address = $request->get("address");
        $bank->updated_by = Auth::user()->id;
        $bank->status = "Approved";
        $bank->is_reserve_bank = ($request->get("is_reserve_bank") ? 1 : 0);
        $bank->is_service_fee_bank = $isServiceFeeBank;
        $bank->is_trn_fee_bank = $isTrnFeeBank;

        $bank->save();

        return redirect("/finance-management/banks/edit/" . $bank->id)->with('success', "Bank details added successfully");
    }

    public function editBank($id)
    {
        $bank = Bank::find($id);
        $currencies = Currency::where("is_active", true)->get();
        return view("finance-management.banks.edit", compact('currencies', 'bank'));
    }

    public function updateBank(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'address' => 'required'
        ]);

        $bank = Bank::find($request->get("bank_id"));
        $isServiceFeeBank = false;
        $isTrnFeeBank = false;

        if (array_key_exists('fee_type', $request->all())) {
            if ('service_fee_bank' == $request->get('fee_type')) {
                $isServiceFeeBank = true;
            } else {
                $isTrnFeeBank = true;
            }
        }

        if ($request->get("is_reserve_bank") == 1) {
            $existingReserveBank = Bank::where('is_reserve_bank', true)->first();
            if ($existingReserveBank && $existingReserveBank->id != $request->get("bank_id")) {
                return redirect("/finance-management/banks/edit/" . $request->get("bank_id"))
                    ->with('error', "Only one bank can be set as the Reserve Bank. The existing Reserve Bank is: " . $existingReserveBank->name);
            }
        }

        $bank->name = $request->get("name");
        $bank->address = $request->get("address");
        $bank->updated_by = Auth::user()->id;
        $bank->status = "Approved";
        $bank->is_reserve_bank = ($request->get("is_reserve_bank") ? 1 : 0);
        $bank->is_service_fee_bank = $isServiceFeeBank;
        $bank->is_trn_fee_bank = $isTrnFeeBank;

        $bank->save();

        return redirect("/finance-management/banks/edit/" . $request->get("bank_id"))->with('success', "Bank details updated successfully");
    }

    public function storeBankAccount(Request $request)
    {
        $this->validate($request, [
            'title' => 'required',
            'account_number' => 'required',
            'account_currency' => 'required'
        ]);

        $account = new Account();
        $isDuplicating = Account::where('account_number', $request->get("account_number"))->first();

        if ($isDuplicating === null) {

            $account->bank_id = $request->get("bank_id");
            $account->type = "bank";
            $account->title = $request->get("title");
            $account->account_number = $request->get("account_number");
            $account->account_currency = $request->get("account_currency");
            $account->is_active = true;
            $account->is_crypto = Common::isCryptoCurrency($request->get("account_currency"));
            $account->created_by = Auth::user()->id;

            $account->save();

            return redirect("/finance-management/banks/edit/" . $request->get("bank_id"))->with('success', "Bank account added successfully");
        }

        return redirect("/finance-management/banks/edit/" . $request->get("bank_id"))->with('error', "The Account number already exists");
    }

    public function deleteBankAccount(Request $request)
    {
        $account = Account::find($request->get("account_id"));

        if ($account && $account->transactions()->count()) {
            return redirect("/finance-management/banks/edit/" . $request->get("bank_id"))->with('error', "The account can't be deleted due to present transactions, need to delete transactions first");
        } else {
            $account->delete();
        }

        return redirect("/finance-management/banks/edit/" . $request->get("bank_id"))->with('success', "Bank account is deleted successfully");
    }

    public function deleteBank(Request $request)
    {
        $bank = Bank::find($request->get("bank_id"));

        if ($bank->accounts->count() > 0) {
            return redirect("/finance-management/banks")->with('error', "Bank account cannot be deleted, please delete bank accounts first.");
        }

        $bank->delete();

        return redirect("/finance-management/banks")->with('success', "Bank account deleted successfully.");
    }

    public function indexBankSSI()
    {
        $banks = Bank::whereHas('bankSSI')->with('bankSSI')->get();
        return view("finance-management.banks-ssi.list", compact('banks'));
    }

    public function addBankSSI()
    {
        $banks = Bank::all();
        $currencies = Currency::where("is_active", true)->where("is_crypto", false)->get();
        $bankSSIs = BanksSSI::all();
        return view("finance-management.banks-ssi.new", compact('banks', 'currencies', 'bankSSIs'));
    }

    public function storeBankSSI(Request $request)
    {
        $this->validateBankSSIRequest($request);

        if ($this->bankSSIExists($request->bank_id, $request->currency_id)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'A Bank SSI with the same Bank and Currency already exists. Only one SSI is allowed per currency for a bank.');
        }

        $ssiDetails = $this->prepareSSIDetails($request);

        if ($request->has('is_active')) {
            $this->deactivateCurrentlyActiveSSI($request);
        }

        $bankSSI = new BanksSSI();

        $bankSSI->bank_id = $request->get('bank_id');
        $bankSSI->currency_id = $request->get('currency_id');
        $bankSSI->ssi_details = json_encode($ssiDetails);
        $bankSSI->is_active = ($request->get("is_active")) ? 1 : 0;
        $bankSSI->created_by = Auth::user()->id;

        $bankSSI->save();

        return redirect('/finance-management/bank-ssi')->with('success', 'Bank SSI created successfully.');
    }

    public function deleteBankSSI(Request $request)
    {
        $bankSSI = BanksSSI::where('id', $request->bankssi_id)
            ->where('bank_id', $request->bank_id)
            ->firstOrFail();

        if ($bankSSI->is_active) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'This is the current active SSI for the selected currency. Please Activate another SSI for this currency first.');
        }

        $bankSSI->delete();

        return redirect('/finance-management/bank-ssi')->with('success', 'Bank SSI deleted successfully.');
    }

    public function editBankSSI($id, $ssi_id)
    {
        $bankSSI = BanksSSI::where('id', $ssi_id)
            ->where('bank_id', $id)
            ->firstOrFail();

        $bank = Bank::findOrFail($id);
        $currencies = Currency::where("is_active", true)->where("is_crypto", false)->get();
        $ssiDetails = json_decode($bankSSI->ssi_details, true);
        $bankSSIs = BanksSSI::all();

        return view("finance-management.banks-ssi.edit", compact('bank', 'bankSSI', 'bankSSIs', 'currencies', 'ssiDetails'));
    }

    public function updateBankSSI(Request $request)
    {
        $this->validateBankSSIRequest($request);

        if ($this->bankSSIExists($request->bank_id, $request->currency_id, $request->bankssi_id)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'A Bank SSI with the same Bank and Currency already exists. Only one SSI is allowed per currency for a bank.');
        }

        $ssiDetails = $this->prepareSSIDetails($request);
        $bankSSI = BanksSSI::findOrFail($request->bankssi_id);

        if ($request->has('is_active')) {
            $this->deactivateCurrentlyActiveSSI($request);
        }

        $bankSSI->currency_id = $request->get('currency_id');
        $bankSSI->ssi_details = json_encode($ssiDetails);
        $bankSSI->is_active = ($request->get("is_active")) ? 1 : 0;
        $bankSSI->updated_by = Auth::user()->id;

        $bankSSI->save();

        return redirect('/finance-management/bank-ssi')->with('success', 'Bank SSI updated successfully.');
    }

    private function validateBankSSIRequest(Request $request)
    {
        $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'currency_id' => 'required|exists:currencies,id',
            'swift_code' => 'required',
            'bank_name' => 'required',
            'bank_address' => 'required',
            '57a_swift_code' => 'required',
            '57a_bank_name' => 'required',
            '57a_bank_address' => 'required',
            'account_number' => 'required',
            'account_name' => 'required',
            'address' => 'required',
            'remittance_information' => 'required',
            'is_active' => 'boolean'
        ]);
    }

    private function bankSSIExists($bankId, $currencyId, $excludeId = null)
    {
        $query = BanksSSI::where('bank_id', $bankId)
            ->where('currency_id', $currencyId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function prepareSSIDetails(Request $request)
    {
        return [
            'Currency' => $request->currency_short_name,
            '56A: Intermediary Institution - BIC' => [
                'SWIFT Code' => $request->swift_code,
                'Bank Name' => $request->bank_name,
                'Bank Address' => $request->bank_address,
            ],
            '57A: Account with the Institution - BIC' => [
                'SWIFT Code' => $request->{'57a_swift_code'},
                'Bank Name' => $request->{'57a_bank_name'},
                'Bank Address' => $request->{'57a_bank_address'},
            ],
            '59: Beneficiary Customer - Name & Address' => [
                'Account Number' => $request->account_number,
                'Account Name' => $request->account_name,
                'Address' => $request->address,
            ],
            '70: Remittance Information' => [
                'Remittance Information' => $request->remittance_information,
                'Customer Name' => "",
                'Account Number' => "",
            ],
        ];
    }

    private function deactivateCurrentlyActiveSSI($request)
    {
        $activeSSI = BanksSSI::where('currency_id', $request->currency_id)
            ->where('is_active', 1)
            ->first();

        if ($activeSSI) {
            $activeSSI->is_active = 0;

            $activeSSI->save();
        }
    }
}
