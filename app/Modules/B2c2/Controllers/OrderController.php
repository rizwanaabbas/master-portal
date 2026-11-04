<?php

namespace App\Modules\B2c2\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\B2c2\Services\OrderService;
use App\Modules\Liquidity\Models\Rfq;
use App\Models\Account;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rfq_id'           => 'required|string',
            'instrument'       => 'required|string',
            'currency'         => 'nullable|string',
            'side'             => 'required|string|in:buy,sell',
            'quantity'         => 'required|numeric|gt:0',
            'client_order_id'  => 'nullable|string',
            'order_type'       => 'nullable|string|in:FOK,IOC,GTC',
            'executing_unit'   => 'nullable|string',
            'crypto_account_id' => 'required|integer|exists:accounts,id',
            'fiat_account_id'  => 'required|integer|exists:accounts,id',
            'remarks'          => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Resolve the quote server-side. Never trust price/valid_until from the
        // client: use the latest RFQ for this rfq_id, and only if it belongs to
        // the current user.
        $rfq = Rfq::provider('b2c2')
            ->where('rfq_id', $request->rfq_id)
            ->where('created_by', Auth::id())
            ->latest('id')
            ->first();

        if (!$rfq) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or unauthorized quote. Please request a new quote.',
            ], 422);
        }

        if (!$rfq->price) {
            return response()->json([
                'success' => false,
                'message' => 'This quote has no price. Please request a new quote.',
            ], 422);
        }

        // The quote must match the order the client is trying to place.
        if ($rfq->instrument !== $request->instrument || $rfq->side !== $request->side) {
            return response()->json([
                'success' => false,
                'message' => 'The quote does not match the requested instrument/side. Please request a new quote.',
            ], 422);
        }

        if ($rfq->valid_until && now()->greaterThan($rfq->valid_until)) {
            return response()->json([
                'success' => false,
                'message' => 'This quote has expired. Please request a new quote.',
            ], 422);
        }

        $cryptoAccount = Account::find($request->crypto_account_id);
        $fiatAccount = Account::find($request->fiat_account_id);

        if (!$cryptoAccount->is_crypto) {
            return response()->json([
                'success' => false,
                'message' => 'crypto_account_id must be a crypto account.',
            ], 422);
        }

        if ($fiatAccount->is_crypto) {
            return response()->json([
                'success' => false,
                'message' => 'fiat_account_id must be a fiat account.',
            ], 422);
        }

        if ($cryptoAccount->client_id !== $fiatAccount->client_id) {
            return response()->json([
                'success' => false,
                'message' => 'Both accounts must belong to the same client.',
            ], 422);
        }

        if ($cryptoAccount->id === $fiatAccount->id) {
            return response()->json([
                'success' => false,
                'message' => 'Please choose different accounts.',
            ], 422);
        }

        // Balance guard: the source account must cover the quote total.
        //   buy  -> debit the fiat account by price * quantity
        //   sell -> debit the crypto account by quantity
        $quantity = (float) $request->quantity;
        if ($request->side === 'buy') {
            $sourceAccount = $fiatAccount;
            $requiredFromSource = (float) $rfq->price * $quantity;
        } else {
            $sourceAccount = $cryptoAccount;
            $requiredFromSource = $quantity;
        }

        $sourceBalance = (float) Common::getAccountBalanceById($sourceAccount->id);
        $sourceCcy = optional($sourceAccount->currency)->short;

        if ($requiredFromSource > $sourceBalance) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance in the source account. Required: '
                    . $requiredFromSource . ' ' . $sourceCcy
                    . ', available: ' . $sourceBalance . ' ' . $sourceCcy . '.',
            ], 422);
        }

        // Authoritative values come from the stored quote, not the request.
        // Send B2c2 the original UTC valid_until it issued (from raw_response),
        // falling back to the stored value expressed in UTC.
        $validUntilForApi = $rfq->raw_response['valid_until']
            ?? optional($rfq->valid_until)->clone()->setTimezone('UTC')->toIso8601String()
            ?? $request->valid_until;

        $params = array_merge($request->all(), [
            'rfq_id'      => $rfq->rfq_id,
            'price'       => $rfq->price,
            'valid_until' => $validUntilForApi,
            'created_by'  => Auth::id(),
        ]);

        try {
            $result = $this->orderService->createOrder(
                $params,
                $cryptoAccount,
                $fiatAccount
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to create order.',
                ], 502);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $orderId)
    {
        try {
            $result = $this->orderService->getOrder($orderId);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Order not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['status', 'instrument', 'side']);
            $result = $this->orderService->getLocalOrders($filters);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
