<?php

namespace App\Modules\Aquanow\Services;

use App\Modules\Liquidity\Models\Instrument;
use App\Modules\Liquidity\Models\Order;
use App\Modules\Liquidity\Models\Rfq;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\TRRequest;
use App\Helpers\CryptoLogHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderService
{
    protected ClientService $client;

    public function __construct(ClientService $client)
    {
        $this->client = $client;
    }

    public function createOrder(array $params, Account $cryptoAccount, Account $fiatAccount): array
    {
        $side = $params['side'];
        $quantity = $params['quantity'];
        $price = $params['price'];
        $totalAmount = $quantity * $price;

        // Don't hit Aquanow at all for an instrument we don't have enabled —
        // fail fast with a generic message instead of a confusing API error.
        if (!$this->instrumentAvailable($params['instrument'], $cryptoAccount->account_currency, $fiatAccount->account_currency)) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Instrument not available.',
            ];
        }

        // Execute the quote obtained during RFQ — Aquanow's order placement
        // for a quoted trade is a single executeQuote call keyed by quoteId.
        $payload = array_filter([
            'quoteId'   => $params['rfq_id'],
            'accountId' => ConfigService::get()['credit_account_id'] ?? null,
        ], fn ($value) => $value !== null);

        $status = 'Approved';
        $order = Order::create([
            'provider'        => 'aquanow',
            'client_order_id' => $params['client_order_id'] ?? (string) \Illuminate\Support\Str::uuid(),
            'rfq_id'          => $params['rfq_id'] ?? null,
            'created_by'      => $params['created_by'] ?? Auth::id(),
            'instrument'      => $params['instrument'],
            'side'            => $side,
            'quantity'        => $quantity,
            'price'           => $price,
            'order_type'      => $params['order_type'] ?? 'FOK',
            'status'          => $status,
            'raw_request'     => $payload,
        ]);

        $response = $this->client->post('trades/v1/executeQuote', $payload);

        if ($response['success']) {
            $data = $response['data'];
            // executeQuote's ack only confirms {"payload":{"quoteId":...}} — it
            // doesn't return fill details. A quote execution is atomic (it
            // either succeeds at the quoted price/quantity or comes back as
            // an `error` — handled as failure by ClientService already), so a
            // successful response means the full quoted quantity filled.
            $execPayload = $data['payload'] ?? [];

            $order->update([
                'order_id'         => $execPayload['orderId'] ?? $execPayload['quoteId'] ?? null,
                'filled_amount'    => $execPayload['filledQuantity'] ?? $quantity,
                'remaining_amount' => $execPayload['remainingQuantity'] ?? 0,
                'status'           => 'Approved',
                'raw_response'     => $data,
            ]);

            $executedRfqId = $params['rfq_id'] ?? null;
            if (!empty($executedRfqId)) {
                Rfq::provider('aquanow')->where('rfq_id', $executedRfqId)->update(['status' => 'executed']);
            }

            // Fee Engine: charge the fee (in FIAT, on the fiat leg) and record it
            // on the order. Never let a fee failure break the order.
            $subTotal   = (float) $totalAmount;
            $feeAmount  = 0.0;
            $feeType    = null;
            $feeRate    = null;
            $feeChargeIds = [];

            try {
                if (\App\Modules\FeeEngine\Services\ConfigService::isEnabled()) {
                    $direction = $side === 'buy' ? 'debit' : 'credit';
                    $feeResult = app(\App\Modules\FeeEngine\Services\FeeCalculationService::class)->charge(
                        $fiatAccount,
                        $subTotal,
                        $direction,
                        (string) ($order->order_id ?? $order->id),
                        'aquanow_' . $side,
                        [
                            'aquanow_order_id'    => $order->order_id,
                            'aquanow_local_order' => $order->id,
                            'rfq_id'              => $params['rfq_id'] ?? null,
                            'price'               => $price,
                            'crypto_quantity'     => $quantity,
                        ],
                        $cryptoAccount
                    );

                    $feeAmount = (float) ($feeResult['total_fee'] ?? 0);
                    [$feeType, $feeRate] = $this->summariseFee($feeResult['charges'] ?? []);
                    $feeChargeIds = $feeResult['charge_ids'] ?? [];
                }
            } catch (\Throwable $e) {
                Log::error('[FeeEngine] Failed to charge Aquanow order fees', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }

            $grandTotal = $side === 'buy' ? ($subTotal + $feeAmount) : ($subTotal - $feeAmount);

            $txIds = $this->createTransactionRecords(
                $order, $cryptoAccount, $fiatAccount, $side, $quantity, $price, $grandTotal, $params['remarks'] ?? null
            );

            $order->update([
                'sub_total'          => $subTotal,
                'fee'                => $feeAmount,
                'fee_type'           => $feeType,
                'fee_rate'           => $feeRate,
                'grand_total'        => $grandTotal,
                'fiat_currency_id'   => $fiatAccount->account_currency,
                'crypto_currency_id' => $cryptoAccount->account_currency,
                'tr_request_id'      => $txIds['tr_request_id'] ?? null,
                'dr_tr_id'           => $txIds['dr_tr_id'] ?? null,
                'cr_tr_id'           => $txIds['cr_tr_id'] ?? null,
            ]);

            if (!empty($feeChargeIds) && !empty($txIds['tr_request_id'])) {
                \App\Modules\FeeEngine\Models\FeeTransactionCharge::whereIn('id', $feeChargeIds)
                    ->update(['tr_request_id' => $txIds['tr_request_id']]);
            }

            CryptoLogHelper::logCryptoTransaction(
                $data['orderId'] ?? $order->client_order_id,
                $response,
                'Aquanow',
                ucfirst($side)
            );
        } else {
            $order->update([
                'status'        => 'failed',
                'error_message' => $response['message'] ?? json_encode($response['data']),
                'raw_response'  => $response['data'],
            ]);

            CryptoLogHelper::logCryptoTransaction(
                $order->client_order_id,
                $response,
                'Aquanow',
                ucfirst($side) . ' Failed'
            );
        }

        return [
            'success' => $response['success'],
            'data' => $order->fresh(),
            'message' => $response['message'] ?? null,
        ];
    }

    protected function createTransactionRecords(Order $order, Account $cryptoAccount, Account $fiatAccount, string $side, float $quantity, float $price, float $totalAmount, ?string $remarks): array
    {
        try {
            $status = strtoupper($order->status) === 'APPROVED' ? 'Approved' : 'Pending';

            $trTime = now();

            if ($side === 'buy') {
                $cryptoType = 'Credit';
                $fiatType = 'Debit';
                $nature = 'Buy';
                $trRequestAccount = $fiatAccount;
            } else {
                $cryptoType = 'Debit';
                $fiatType = 'Credit';
                $nature = 'Sell';
                $trRequestAccount = $cryptoAccount;
            }

            $cryptoCurrencyId = $cryptoAccount->account_currency;
            $fiatCurrencyId = $fiatAccount->account_currency;

            $cryptoAddress = \App\Models\CryptoAddress::where('account_id', $cryptoAccount->id)
                ->latest('id')
                ->first();
            $toWalletAddress = $cryptoAddress->crypto_address ?? null;
            $cryptoNetwork = $cryptoAddress->network ?? null;
            $cryptoDetailsId = $order->id;

            $crTrn = new AccountTransaction();
            $crTrn->account_id = $cryptoAccount->id;
            $crTrn->crypto_transaction_id = $order->order_id;
            $crTrn->crypto_details_id = $cryptoDetailsId;
            $crTrn->to_wallet_address = $toWalletAddress;
            $crTrn->crypto_network = $cryptoNetwork;
            $crTrn->currency_id = $cryptoCurrencyId;
            $crTrn->transaction_type = $cryptoType;
            $crTrn->transaction_nature = $nature;
            $crTrn->transaction_date = $trTime;
            $crTrn->amount = $quantity;
            $crTrn->is_crypto = 1;
            $crTrn->remarks = $remarks;
            $crTrn->status = $status;
            $crTrn->crypto_system = 'Aquanow';
            $crTrn->created_by = Auth::id();
            $crTrn->approved_by = 0;
            $crTrn->save();

            $dtTrn = new AccountTransaction();
            $dtTrn->account_id = $fiatAccount->id;
            $dtTrn->link_tr_id = $crTrn->id;
            $dtTrn->crypto_transaction_id = $order->order_id;
            $dtTrn->crypto_details_id = $cryptoDetailsId;
            $dtTrn->to_wallet_address = $toWalletAddress;
            $dtTrn->crypto_network = $cryptoNetwork;
            $dtTrn->currency_id = $fiatCurrencyId;
            $dtTrn->transaction_type = $fiatType;
            $dtTrn->transaction_nature = $nature;
            $dtTrn->transaction_date = $trTime;
            $dtTrn->amount = $totalAmount;
            $dtTrn->is_crypto = 1;
            $dtTrn->remarks = $remarks;
            $dtTrn->status = $status;
            $dtTrn->crypto_system = 'Aquanow';
            $dtTrn->created_by = Auth::id();
            $dtTrn->approved_by = 0;
            $dtTrn->save();

            $crTrn->link_tr_id = $dtTrn->id;
            $crTrn->save();

            $trRequest = new TRRequest();
            $trRequest->account_id = $trRequestAccount->id;
            $trRequest->receiver_account_number = $cryptoAccount->account_number;
            $trRequest->crypto_details_id = $cryptoDetailsId;
            $trRequest->to_wallet_address = $toWalletAddress;
            $trRequest->crypto_network = $cryptoNetwork;
            $trRequest->currency_id = $trRequestAccount->account_currency;
            $trRequest->transaction_type = $fiatType;
            $trRequest->transaction_nature = $nature;
            $trRequest->transaction_date = $trTime;
            $trRequest->amount = $totalAmount;
            $trRequest->crypto_amount = $quantity;
            $trRequest->remarks = $remarks;
            $trRequest->status = $status;
            $trRequest->crypto_system = 'Aquanow';
            $trRequest->crypto_transaction_id = $order->order_id;
            $trRequest->created_by = Auth::id();
            $trRequest->approved_by = 0;
            $trRequest->save();

            $trId = $trRequest->id;
            $dtTrn->tr_request_id = $trId;
            $dtTrn->save();
            $crTrn->tr_request_id = $trId;
            $crTrn->save();

            Log::info('Aquanow order transaction records created', [
                'order_id' => $order->id,
                'aquanow_order_id' => $order->order_id,
                'tr_request_id' => $trId,
                'credit_trn_id' => $crTrn->id,
                'debit_trn_id' => $dtTrn->id,
            ]);

            return [
                'tr_request_id' => $trId,
                'dr_tr_id'      => $cryptoType === 'Debit' ? $crTrn->id : $dtTrn->id,
                'cr_tr_id'      => $cryptoType === 'Credit' ? $crTrn->id : $dtTrn->id,
            ];
        } catch (\Exception $e) {
            Log::error('Aquanow failed to create transaction records', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function summariseFee(array $charges): array
    {
        if (empty($charges)) {
            return [null, null];
        }

        if (count($charges) === 1) {
            $c = $charges[0];
            return [$c['fee_type'], $c['fee_type'] === 'percentage' ? (float) $c['fee_rate'] : null];
        }

        return ['mixed', null];
    }

    public function getOrder(string $orderId): array
    {
        $response = $this->client->get('trades/v2/order', ['orderId' => $orderId]);

        if ($response['success']) {
            $data = $response['data'];
            Order::provider('aquanow')->where('order_id', $orderId)->update([
                'status'           => $data['status'] ?? null,
                'filled_amount'    => $data['filledQuantity'] ?? null,
                'remaining_amount' => $data['remainingQuantity'] ?? null,
                'price'            => $data['price'] ?? null,
                'raw_response'     => $data,
            ]);
        }

        return [
            'success' => $response['success'],
            'data' => $response['data'],
            'message' => $response['message'] ?? null,
        ];
    }

    /**
     * Whether this instrument is synced, active, and thus tradeable for
     * Aquanow — checked before executing a quote so an instrument that's
     * been disabled between quoting and execution fails fast with a generic
     * message rather than a raw API error.
     *
     * The ticker itself never flips direction (XRP-USD covers both buy and
     * sell), but since we also know the two accounts' currencies here, match
     * them against the instrument in either from/to order too.
     */
    protected function instrumentAvailable(string $instrument, ?int $cryptoCurrencyId = null, ?int $fiatCurrencyId = null): bool
    {
        return Instrument::provider('aquanow')
            ->where('is_active', true)
            ->where(function ($query) use ($instrument, $cryptoCurrencyId, $fiatCurrencyId) {
                $query->where('instrument', $instrument);

                if ($cryptoCurrencyId && $fiatCurrencyId) {
                    $query->orWhere(fn ($q) => $q->forCurrencyPair($cryptoCurrencyId, $fiatCurrencyId));
                }
            })
            ->exists();
    }

    public function getLocalOrders(array $filters = []): array
    {
        $query = Order::provider('aquanow');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['instrument'])) {
            $query->where('instrument', $filters['instrument']);
        }

        if (!empty($filters['side'])) {
            $query->where('side', $filters['side']);
        }

        return [
            'success' => true,
            'data' => $query->orderBy('created_at', 'desc')->get(),
        ];
    }
}
