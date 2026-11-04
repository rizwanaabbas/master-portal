<?php

namespace App\Modules\Fireblocks\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Helpers\ClientHelper;
use App\Helpers\Common;
use App\Helpers\CryptoLogHelper;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Currency;
use App\Models\TRRequest;
use App\Modules\Fireblocks\Models\Asset;
use App\Modules\Fireblocks\Models\Network;
use App\Modules\Fireblocks\Models\CryptoBeneficiary;
use App\Modules\Fireblocks\Services\WithdrawalService;
use App\Modules\Fireblocks\Services\BeneficiaryService;
use App\Modules\Fireblocks\Services\FireblocksConfigService;
use App\Modules\Fireblocks\Services\WithdrawalSourceResolver;
use App\Modules\FireblocksTransactionsMonitoring\Services\ChainalysisComplianceSyncService;

/**
 * Handles the Fireblocks withdrawal request only: validates the request, creates the
 * TR request + account transaction as Pending, and submits the transaction to
 * Fireblocks. Final status (Approved/Rejected/Cancelled) is resolved exclusively by
 * the Fireblocks webhook (see WebhookService::handleTransactionEvent) once Fireblocks
 * confirms the transaction on-chain — this controller does not resolve or finalise
 * status itself.
 *
 * Beneficiary CRUD/listing and address compliance screening are owned by
 * BeneficiaryCrudController/BeneficiaryService (screened once at whitelisting time) —
 * this controller does not duplicate that logic.
 */
class FireblocksWithdrawController extends Controller
{
    protected WithdrawalService $withdrawalService;
    protected BeneficiaryService $beneficiaryService;

    public function __construct(WithdrawalService $withdrawalService, BeneficiaryService $beneficiaryService)
    {
        $this->middleware(['auth', '2fa', 'permission:fireblocks custody']);
        $this->withdrawalService = $withdrawalService;
        $this->beneficiaryService = $beneficiaryService;
    }

    /**
     * Show the minimal Fireblocks withdraw form.
     */
    public function create()
    {
        $currencies = Currency::where('is_active', true)->where('is_crypto', true)->select('id')->get();
        $cryptoAccounts = Account::where('is_active', true)
            ->whereIn('account_currency', $currencies)
            ->select('id', 'title', 'account_number')
            ->get();

        return view('fireblocks::withdraw', compact('cryptoAccounts'));
    }

    /**
     * AJAX — active beneficiaries for an account (populates the withdraw dropdown).
     */
    public function beneficiaries($account_id)
    {
        $result = $this->beneficiaryService->listForAccount((int) $account_id);

        $beneficiaries = collect($result['data'] ?? [])
            ->map(function ($b) {
                return [
                    'id'              => $b->id,
                    'label'           => $b->label,
                    'wallet_address'  => $b->wallet_address,
                    'destination_tag' => $b->destination_tag,
                    'network_code'    => optional($b->network)->code,
                    'network_name'    => optional($b->network)->name,
                ];
            });

        return response()->json($beneficiaries);
    }

    /**
     * Process the withdrawal request: create the TR request + account transaction as
     * Pending, submit to Fireblocks, and stop. Status is finalised later by the
     * Fireblocks webhook, not here.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'account_id'           => 'required|numeric',
            'crypto_beneficiary_id' => 'required|numeric',
            'amount'               => 'required|numeric|gt:0',
            'remarks'              => 'nullable|string|max:92',
        ]);

        $account = Account::find($request->account_id);
        if (!$account || !$account->is_active) {
            return redirect()->back()->withInput()->with('error', 'Account not found or is not active.');
        }

        $client = ClientHelper::getClientByAccountId($account->id);
        $currency = Currency::find($account->account_currency);
        if (!$currency) {
            return redirect()->back()->withInput()->with('error', 'Currency not found.');
        }

        // Amount within currency limits
        $this->validate($request, [
            'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
        ]);

        // Balance check
        $balance = Common::getAccountBalanceById($account->id);
        if ($request->get('amount') > $balance) {
            return redirect()->back()->withInput()->with('error', "Insufficient balance in the account.");
        }

        // Beneficiary must belong to the account
        $beneficiary = CryptoBeneficiary::with('externalWallet')
            ->where('id', $request->crypto_beneficiary_id)
            ->where('account_id', $account->id)
            ->where('is_active', true)
            ->first();

        if (!$beneficiary) {
            return redirect()->back()->withInput()->with('error', 'Selected beneficiary not found for this account.');
        }

        // Note: the destination address was already Chainalysis-screened when it was
        // whitelisted as a beneficiary (see BeneficiaryCrudController@store) — no need
        // to re-screen it here.

        // Resolve network + asset + source vault
        $network = Network::enabled()->where('code', $beneficiary->network->code ?? '')
            ->where('currency_id', $account->account_currency)
            ->first();
        if (!$network) {
            return redirect()->back()->withInput()->with('error', 'Network not configured for this currency.');
        }

        $asset = Asset::enabled()
            ->where('network_id', $network->id)
            ->where('currency_id', $account->account_currency)
            ->first();
        if (!$asset || empty($asset->asset_id)) {
            return redirect()->back()->withInput()->with('error', 'Asset not configured for the selected network.');
        }
        $withdrawalSource = WithdrawalSourceResolver::resolveForAsset($asset);
        if (empty($withdrawalSource['source_vault_account_id']) && empty($withdrawalSource['wallet_pool_id'])) {
            return redirect()->back()->withInput()->with('error', 'Withdrawal source not configured for this asset (UTXO vault or Wallet Pool ID).');
        }

        $fireblocksWalletId = $beneficiary->fireblocks_wallet_id
            ?: $beneficiary->externalWallet?->fireblocks_wallet_id;

        if (empty($fireblocksWalletId)) {
            return redirect()->back()->withInput()->with(
                'error',
                'Beneficiary is not linked to a Fireblocks EXTERNAL_WALLET. Re-add the beneficiary before withdrawing.'
            );
        }

        $transactionDateTime = Carbon::now();

        // Only build/send the Travel Rule message when the module has it enabled —
        // sending it otherwise means fields the beneficiary form never collected.
        $travelRuleMessage = FireblocksConfigService::travelRulesEnabled()
            ? $this->buildTravelRuleMessage($beneficiary, $client, $asset->asset_id, $request->get('amount'))
            : null;

        DB::beginTransaction();
        try {
            // 1) Create the withdrawal request as Pending
            $req = new TRRequest();
            $req->account_id         = $account->id;
            $req->currency_id        = $account->account_currency;
            $req->transaction_type   = 'Debit';
            $req->transaction_nature = 'Withdraw';
            $req->transaction_date   = $transactionDateTime;
            $req->crypto_amount      = $request->get('amount');
            $req->to_wallet_address  = $beneficiary->wallet_address;
            $req->crypto_network     = $beneficiary->network->code ?? null;
            $req->destination_tag    = $beneficiary->destination_tag;
            $req->remarks            = $request->get('remarks');
            $req->crypto_system      = 'fireblocks';
            $req->crypto_details_id  = (string) $beneficiary->id;
            $req->status             = 'Pending';
            $req->otp_verified       = true;
            $req->created_by         = Auth::id();
            $req->save();

            // 2) Submit to Fireblocks (EXTERNAL_WALLET only — never OTA)
            $withdrawPayload = array_merge([
                'asset_id'                => $asset->asset_id,
                'destination_address'     => $beneficiary->wallet_address,
                'fireblocks_wallet_id'    => $fireblocksWalletId,
                'fireblocks_asset_status' => $beneficiary->fireblocks_asset_status,
                'amount'                  => $request->get('amount'),
                'destination_tag'         => $beneficiary->destination_tag,
                'note'                    => $request->get('remarks'),
                'external_tx_id'          => (string) $req->id,
                'customer_ref_id'         => (string) $account->client_id,
                'travel_rule_message'     => $travelRuleMessage,
                'defer_failed_submission_record' => true,
                'failure_alert_context'   => [
                    'account_id' => $account->id,
                    'channel'    => 'Client Portal',
                ],
            ], $withdrawalSource);

            $result = $this->withdrawalService->withdraw($withdrawPayload);

            if (!$result['success']) {
                $errorMsg = $result['error'] ?? (isset($result['errors']) ? json_encode($result['errors']) : 'Unknown error');

                try {
                    $fbRecord = $this->withdrawalService->recordFailedSubmission(
                        $withdrawPayload,
                        new \RuntimeException($errorMsg)
                    );
                    $this->withdrawalService->attachPendingLedgerForFailedSubmission($fbRecord, $req);
                    DB::commit();

                    return redirect()->back()->withInput()->with(
                        'error',
                        'Fireblocks withdrawal could not be submitted (custody pending). Ledger entry is Pending — resubmit from Transaction Monitoring when funded.'
                    );
                } catch (\Throwable $persistEx) {
                    DB::rollBack();
                    Log::error('FireblocksWithdrawController: failed to persist submission + ledger', [
                        'error' => $persistEx->getMessage(),
                        'withdraw_error' => $errorMsg,
                    ]);

                    return redirect()->back()->withInput()->with('error', 'Fireblocks withdrawal failed: ' . $errorMsg);
                }
            }

            $crypto_order_id = $result['data']['fireblocks_tx_id'];
            CryptoLogHelper::logCryptoTransaction($crypto_order_id, $result, 'Fireblocks', 'system');

            try {
                app(ChainalysisComplianceSyncService::class)->applyStoredBeneficiaryScreeningForWithdraw(
                    $crypto_order_id,
                    $beneficiary->wallet_address,
                    (int) $beneficiary->id,
                );
            } catch (\Throwable $e) {
                Log::warning('FireblocksWithdrawController: Chainalysis monitoring log failed. ' . $e->getMessage());
            }

            // 3) Create the account transaction as Pending — the webhook resolves
            // the final status once Fireblocks confirms the transaction.
            $trn = new AccountTransaction();
            $trn->account_id           = $req->account_id;
            $trn->tr_request_id        = $req->id;
            $trn->currency_id          = $req->currency_id;
            $trn->transaction_type     = 'Debit';
            $trn->transaction_nature   = $req->transaction_nature;
            $trn->transaction_date     = $req->transaction_date;
            $trn->amount               = $request->get('amount');
            $trn->to_wallet_address    = $req->to_wallet_address;
            $trn->destination_tag      = $req->destination_tag;
            $trn->is_crypto            = true;
            $trn->crypto_transaction_id = $crypto_order_id;
            $trn->crypto_system        = 'fireblocks';
            $trn->remarks              = $request->get('remarks');
            $trn->status               = 'Pending';
            $trn->created_by           = $req->created_by;
            $trn->crypto_network       = $req->crypto_network;
            $trn->save();

            $req->crypto_transaction_id = $crypto_order_id;
            $req->save();

            DB::commit();

            return redirect('/crypto-management/crypto-transactions')->with('info', 'Fireblocks withdrawal submitted and pending confirmation. Refresh to check the latest status.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('FireblocksWithdrawController@store: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'An unexpected error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Build the Travel Rule message from the beneficiary's stored PII.
     *
     * Fireblocks' embedded IVMS101 travelRuleMessage schema requires `name.nameIdentifier`
     * to be an ARRAY (Fireblocks does `nameIdentifier.forEach(...)` server-side) under both
     * naturalPerson and legalPerson — a plain string/object there fails with
     * "obj.nameIdentifier.forEach is not a function". Every declared string field
     * (beneficiaryVASPdid, beneficiaryVASPname, countryOfRegistration, etc.) must also
     * actually be a string — never null — or the create-transaction call is rejected with
     * a oneOf schema validation error.
     *
     * @see https://developers.fireblocks.com/reference/travel-rule-link-integration — example payload
     */
    private function buildTravelRuleMessage(CryptoBeneficiary $b, $client, string $assetId, $amount): array
    {
        $beneficiaryPersons = [];
        if ($b->beneficiary_type === 'INDIVIDUAL') {
            $beneficiaryPersons[] = [
                'naturalPerson' => array_filter([
                    'name' => [
                        [
                            'nameIdentifier' => [
                                array_filter([
                                    'primaryIdentifier'   => (string) ($b->beneficiary_last_name ?? ''),
                                    'secondaryIdentifier' => (string) ($b->beneficiary_first_name ?? ''),
                                ]),
                            ],
                        ],
                    ],
                    'dateAndPlaceOfBirth' => $b->dob ? [
                        'dateOfBirth' => optional($b->dob)->format('Y-m-d'),
                    ] : null,
                    'countryOfResidence' => (string) ($b->country_code ?? ''),
                ], fn ($v) => $v !== null),
            ];
        } else {
            $beneficiaryPersons[] = [
                'legalPerson' => [
                    'name' => [
                        'nameIdentifier' => [
                            [
                                'legalPersonName'               => (string) ($b->beneficiary_legal_name ?? ''),
                                'legalPersonNameIdentifierType' => 'REGISTERED',
                            ],
                        ],
                    ],
                    'countryOfRegistration' => (string) ($b->country_code ?? ''),
                ],
            ];
        }

        return [
            'transactionAsset'    => $assetId,
            'transactionAmount'   => (string) $amount,
            'transactionType'     => 'TRANSFER',
            'originatorVASPdid'   => '',
            'beneficiaryVASPdid'  => (string) ($b->beneficiary_vasp_did ?? ''),
            'beneficiaryVASPname' => (string) ($b->beneficiary_vasp_name ?? ''),
            'walletType'          => (string) ($b->wallet_type ?? ''),
            'isSelfHosted'        => $b->wallet_type === 'SELF_HOSTED',
            'ownershipDeclared'   => (bool) $b->is_ownership_declaration,
            'sendingToSelf'       => (bool) $b->is_sending_to_myself,
            'beneficiary' => [
                'beneficiaryPersons' => $beneficiaryPersons,
                'accountNumber'      => [$b->wallet_address],
            ],
            'originator' => [
                'originatorPersons' => [[
                    'legalPerson' => [
                        'name' => [
                            'nameIdentifier' => [
                                [
                                    'legalPersonName'               => (string) (optional($client)->name ?? ''),
                                    'legalPersonNameIdentifierType' => 'REGISTERED',
                                ],
                            ],
                        ],
                    ],
                ]],
                'accountNumber' => [(string) optional($client)->id],
            ],
        ];
    }
}
