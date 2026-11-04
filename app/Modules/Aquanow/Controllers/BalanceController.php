<?php

namespace App\Modules\Aquanow\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Aquanow\Services\BalanceService;
use App\Modules\Liquidity\Controllers\BalanceController as LiquidityBalanceController;
use App\Modules\Liquidity\Services\ProviderResolver;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    protected BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function index()
    {
        try {
            $result = $this->balanceService->getBalances();
            //dd($result);
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to fetch balances.',
                ], $result['status'] ?: 502);
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

    public function breakdown()
    {
        try {
            $result = $this->balanceService->getBalanceBreakdown();

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to fetch balance breakdown.',
                ], $result['status'] ?: 502);
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

    public function page()
    {
        return app(LiquidityBalanceController::class)->page(ProviderResolver::AQUANOW, [
            'data' => url('/aquanow/balances/data'),
            'sync' => url('/aquanow/balances/live'),
        ]);
    }

    public function gridData(Request $request)
    {
        return app(LiquidityBalanceController::class)->data($request, ProviderResolver::AQUANOW);
    }

    public function local()
    {
        try {
            $result = $this->balanceService->getLocalBalances();

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
