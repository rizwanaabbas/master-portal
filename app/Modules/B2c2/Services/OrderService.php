<?php

namespace App\Modules\B2c2\Services;

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

        $payload = [
            'instrument'      => $params['instrument'],
            'currency'        => $params['currency'] ?? $this->extractCurrency($params['instrument']),
            'side'            => $side,
            'quantity'        => $quantity,
            'client_order_id' => $params['client_order_id'] ?? (string) \Illuminate\Support\Str::uuid(),
            'price'           => $price,
            'order_type'      => $params['order_type'] ?? 'FOK',
            'valid_until'     => $params['valid_until'],
            'executing_unit'  => $params['executing_unit'] ?? 'risk-adding-strategy',
        ];

        if (!empty($params['rfq_id'])) {
            $payload['rfq_id'] = $params['rfq_id'];
        }
        $status = 'Approved';
        $order = Order::create([
            'provider'        => 'b2c2',
            'client_order_id' => $payload['client_order_id'],
            'rfq_id'          => $params['rfq_id'] ?? null,
            'created_by'      => $params['created_by'] ?? \Illuminate\Support\Facades\Auth::id(),
            'instrument'      => $payload['instrument'],
            'side'            => $side,
            'quantity'        => $quantity,
            'price'           => $price,
            'order_type'      => $payload['order_type'],
            'status'          => $status,
            'raw_request'     => $payload,
        ]);

        $response = $this->client->post('order/', $payload);
       
        if ($response['success']) {
            $data = $response['data'];
            //dd($response);
            $data['status']  = 'Pending';
            if($response['status'] === 201) {
                $data['status'] = 'Approved';
            }
            $order->update([
                'order_id'         => $data['order_id'] ?? null,
                'filled_amount'    => $data['filled_amount'] ?? null,
                'remaining_amount' => $data['remaining_amount'] ?? null,
                'status'           => $data['status'] ?? 'Pending', 
                'raw_response'     => $data,
            ]);

            $executedRfqId = $data['rfq_id'] ?? $params['rfq_id'] ?? null;
            if (!empty($executedRfqId)) {
                Rfq::provider('b2c2')->where('rfq_id', $executedRfqId)->update(['status' => 'executed']);
            }

            // Fee Engine: charge the fee (in FIAT, on the fiat leg) and record it
            // on the order. Never let a fee failure break the order.
            //   buy  = client pays fiat (debit); sell = client receives fiat (credit)
            $subTotal   = (float) $totalAmount;   // price * quantity
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
                        'b2c2_' . $side,
                        [
                            'b2c2_order_id'    => $order->order_id,
                            'b2c2_local_order' => $order->id,
                            'rfq_id'           => $params['rfq_id'] ?? null,
                            'price'            => $price,
                            'crypto_quantity'  => $quantity,
                        ],
                        $cryptoAccount // tier resolves on the crypto side; charged in fiat
                    );

                    $feeAmount = (float) ($feeResult['total_fee'] ?? 0);
                    [$feeType, $feeRate] = $this->summariseFee($feeResult['charges'] ?? []);
                    $feeChargeIds = $feeResult['charge_ids'] ?? [];
                }
            } catch (\Throwable $e) {
                Log::error('[FeeEngine] Failed to charge B2c2 order fees', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }

            // Direction-aware settlement:
            //   buy  -> client pays fiat: sub_total + fee (debited)
            //   sell -> client receives fiat: sub_total - fee (credited)
            $grandTotal = $side === 'buy' ? ($subTotal + $feeAmount) : ($subTotal - $feeAmount);

            // The fiat account transaction is settled at the grand total (net of fee).
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

            // Backfill the TR request id onto the fee charge rows (fee is charged
            // before the TR request exists).
            if (!empty($feeChargeIds) && !empty($txIds['tr_request_id'])) {
                \App\Modules\FeeEngine\Models\FeeTransactionCharge::whereIn('id', $feeChargeIds)
                    ->update(['tr_request_id' => $txIds['tr_request_id']]);
            }

            CryptoLogHelper::logCryptoTransaction(
                $data['order_id'] ?? $payload['client_order_id'],
                $response,
                'B2c2',
                ucfirst($side)
            );
        } else {
            $order->update([
                'status'        => 'failed',
                'error_message' => $response['message'] ?? json_encode($response['data']),
                'raw_response'  => $response['data'],
            ]);

            CryptoLogHelper::logCryptoTransaction(
                $payload['client_order_id'],
                $response,
                'B2c2',
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
           //dd($order);
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

            // Wallet address + network come from the crypto account's deposit
            // address; crypto_details_id records the B2c2 order id.
            $cryptoAddress = \App\Models\CryptoAddress::where('account_id', $cryptoAccount->id)
                ->latest('id')
                ->first();
            $toWalletAddress = $cryptoAddress->crypto_address ?? null;
            $cryptoNetwork = $cryptoAddress->network ?? null;
            // crypto_details_id is a bigint; use the local order row id. The B2c2
            // order UUID is already stored in crypto_transaction_id.
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
            $crTrn->crypto_system = 'B2c2';
            $crTrn->created_by = Auth::id();
            $crTrn->approved_by = Auth::id();
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
            $dtTrn->crypto_system = 'B2c2';
            $dtTrn->created_by = Auth::id();
            $dtTrn->approved_by = Auth::id();
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
            $trRequest->crypto_system = 'B2c2';
            $trRequest->crypto_transaction_id = $order->order_id;
            $trRequest->created_by = Auth::id();
            $trRequest->approved_by = Auth::id();
            $trRequest->save();

            $trId = $trRequest->id;
            $dtTrn->tr_request_id = $trId;
            $dtTrn->save();
            $crTrn->tr_request_id = $trId;
            $crTrn->save();

            Log::info('B2c2 order transaction records created', [
                'order_id' => $order->id,
                'b2c2_order_id' => $order->order_id,
                'tr_request_id' => $trId,
                'credit_trn_id' => $crTrn->id,
                'debit_trn_id' => $dtTrn->id,
            ]);

            // Map debit/credit ids regardless of which leg (crypto/fiat) it was.
            return [
                'tr_request_id' => $trId,
                'dr_tr_id'      => $cryptoType === 'Debit' ? $crTrn->id : $dtTrn->id,
                'cr_tr_id'      => $cryptoType === 'Credit' ? $crTrn->id : $dtTrn->id,
            ];
        } catch (\Exception $e) {
            Log::error('B2c2 failed to create transaction records', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Summarise a fee breakdown into a single (type, rate) for the order record.
     * One rule -> its type/rate. Multiple rules -> 'mixed', no single rate.
     */
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
        $response = $this->client->get("order/{$orderId}");

        if ($response['success']) {
            $data = $response['data'];
            Order::provider('b2c2')->where('order_id', $orderId)->update([
                'status'           => $data['status'] ?? null,
                'filled_amount'    => $data['filled_amount'] ?? null,
                'remaining_amount' => $data['remaining_amount'] ?? null,
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

    public function getLocalOrders(array $filters = []): array
    {
        $query = Order::provider('b2c2');

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

    protected function extractCurrency(string $instrument): string
    {
        $parts = explode('.', $instrument);
        $base = $parts[0] ?? '';
        return preg_replace('/USC$|USDT$|USD$/', '', $base);
    }
}
