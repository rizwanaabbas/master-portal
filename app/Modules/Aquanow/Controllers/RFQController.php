<?php

namespace App\Modules\Aquanow\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Modules\Aquanow\Services\RFQService;
use App\Modules\FeeEngine\Services\FeeCalculationService;
use App\Modules\FeeEngine\Services\ConfigService as FeeConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RFQController extends Controller
{
    protected RFQService $rfqService;

    public function __construct(RFQService $rfqService)
    {
        $this->rfqService = $rfqService;
    }

    public function requestQuote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_rfq_id'     => 'nullable|string|max:255',
            'quantity'          => 'required|numeric|gt:0',
            'side'              => 'required|string|in:buy,sell',
            'instrument'        => 'required|string',
            'crypto_account_id' => 'nullable|integer|exists:accounts,id',
            'fiat_account_id'   => 'nullable|integer|exists:accounts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->rfqService->requestQuote($request->all());

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to request quote.',
                ], 502);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'fee'  => $this->feePreview($request, (float) ($result['data']->price ?? 0)),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function feePreview(Request $request, float $price): ?array
    {
        if (!FeeConfig::isEnabled() || !$request->filled('fiat_account_id') || $price <= 0) {
            return null;
        }

        $account = Account::find($request->fiat_account_id);
        if (!$account) {
            return null;
        }

        $tierAccount = $request->filled('crypto_account_id') ? Account::find($request->crypto_account_id) : null;

        $fiatAmount = $price * (float) $request->quantity;
        $direction = $request->side === 'buy' ? 'debit' : 'credit';
        $preview = app(FeeCalculationService::class)->preview($account, $fiatAmount, $direction, $tierAccount);

        if (!$preview['applied']) {
            return null;
        }

        return [
            'applied'        => true,
            'display'        => $preview['crypto_fee_display'],
            'total_fee'      => $preview['total_fee'],
            'net_amount'     => $preview['net_amount'],
            'currency_short' => optional($account->currency)->short,
            'charges'        => array_map(fn ($c) => [
                'label'  => $c['label'],
                'type'   => $c['fee_type'],
                'rate'   => $c['fee_rate'],
                'amount' => $c['fee_amount'],
            ], $preview['charges']),
        ];
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['status', 'instrument']);
            $result = $this->rfqService->getLocalRfqs($filters);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
