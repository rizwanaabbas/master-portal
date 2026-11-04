<?php

namespace App\Http\Controllers;

use App\Helpers\ZenusApis;
use Illuminate\Http\Request;
use App\Models\Currency;
use App\Models\Account;
use App\Http\Controllers\OpenPaydController;
use App\Http\Controllers\LorumController;
use App\Models\VirtualBankAccount;
use App\Models\VirtualBankBeneficiary;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class IBANController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'account_id' => 'required|integer',
        ]);
        $account_id = $request->get('account_id');
        $account = Account::where('id', $account_id)->first();
        $currency = $account->currency;
        $currency_virtual_bank_provider = $account->virtual_bank_provider ?? $currency->virtual_iban_provider;

        /**
         * Decide provider based on currency
         */
        switch ($currency_virtual_bank_provider) {
            case 'openpayd':
                return app(OpenPaydController::class)->createIBAN($request);
            case 'lorum':
                return app(LorumController::class)->createIBAN($request);
            case 'zenus':
                return app(ZenusController::class)->createIBAN($request);
            default:
                return redirect()->back()->with('error', 'No virtual IBAN provider configured for this currency.');
        }
    }

    public function ibanInfo($account_id)
    {

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        if (!empty($account_id)) {
            $bank_account = VirtualBankAccount::where('account_id', $account_id)
                ->select(
                    'iban',
                    'bic',
                    'currency',
                    'account_number',
                    'bank_account_holder_name',
                    'bank_name',
                    'bank_address',
                    'routing_code_entries as bank_routing_codes',
                    'account_info',
                    'provider_name',
                )->first();

            return response()->json([
                'status' => 'success',
                'data' => $bank_account,
            ], 200);
        }
    }

    public function createVirtualBeneficiary(Request $request, $beneficiary, $virtual_bank_provider)
    {
        try {
            $payment_types = $beneficiary->currency->short === "USD" ? ["ACH", "FED_WIRE", "CROSS_BORDER_SWIFT"] : [];
            $virtual_provider = $virtual_bank_provider ?? $beneficiary->currency->virtual_iban_provider;

            // Virtual beneficiary
            $virtual_bank_beneficiary = new VirtualBankBeneficiary();
            $virtual_bank_beneficiary->beneficiary_id = $beneficiary->id;

            if ($request->get('routing_number')) {
                $virtual_bank_beneficiary->bank_routing_codes = json_encode([
                    'routingCodeKey' => 'ROUTING_NUMBER',
                    'routingCodeValue' => $request->get('routing_number'),
                ]);
            }

            $beneficiary_type = $request->get("beneficiary_type") == "individual" ? "individual" : "bussiness";

            $virtual_bank_beneficiary->parent_beneficiary_id = $beneficiary->id;
            $virtual_bank_beneficiary->user_id = $request->user()->id;
            $virtual_bank_beneficiary->friendly_name = $request->get("name");
            $virtual_bank_beneficiary->account_number = $request->get("account_number");
            $virtual_bank_beneficiary->bic = $request->get("swift_code");
            $virtual_bank_beneficiary->currency = $beneficiary->currency->short;
            $virtual_bank_beneficiary->provider_name = $virtual_provider;
            $virtual_bank_beneficiary->status = 'Approved';
            $virtual_bank_beneficiary->bank_account_country = $beneficiary->country->iso;
            $virtual_bank_beneficiary->beneficiary_type = $beneficiary_type;
            $virtual_bank_beneficiary->payment_types = $payment_types;

            $virtual_bank_beneficiary->postal_code = $request->get("bank_postal_code");
            $virtual_bank_beneficiary->bank_account_holder_name = $request->get("bank_account_holder_name");
            $virtual_bank_beneficiary->save();

            return $virtual_bank_beneficiary;
        } catch (\Throwable $e) {
            Log::error('Error creating Virtual beneficiary: ' . $e->getMessage());
            return (object)['error' => true, 'message' => 'Error creating Virtual beneficiary: ' . $e->getMessage()];
        }
    }

    public function getZenusAccountVRN(Request $request)
    {
        $request->validate([
            'virtual_account_id' => 'required|string',
        ]);

        $virtual_account_id = $request->get('virtual_account_id');
        $existing_virtual_account = VirtualBankAccount::where('virtual_bank_account_id', $virtual_account_id)
            ->where('provider_name', 'zenus')
            ->first();

        // Check if VRN exists
        $existing_account_info = json_decode($existing_virtual_account->account_info);
        $hasVrn = collect($existing_account_info)->contains(function ($acc) {
            return $acc->accountNumberSubtype === 'VRN';
        });

        if ($hasVrn) {
            return redirect()->back()->with('warning', 'VRN already exists in for this accounts.');
        }

        if (!empty($existing_virtual_account)) {
            $zenus_api = new ZenusApis();
            $zenus_account = $zenus_api->getAccountOfById($virtual_account_id);
            $account_numbers = $zenus_account->accountNumbers;

            $existing_virtual_account->account_info = json_encode($account_numbers);
            $existing_virtual_account->save();

            return redirect()->back()->with('success', 'Zenus bank VRN, Synced successfully.');
        }

        return redirect()->back()->with('error', 'Zenus bank VRN, synced failed.');
    }
}
