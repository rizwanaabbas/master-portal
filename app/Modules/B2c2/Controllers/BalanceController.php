<?php

namespace App\Modules\B2c2\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\B2c2\Services\BalanceService;
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

    /**
     * Render the Balances management page (local DB data).
     * Live syncing is triggered from the page via the `index` (live) endpoint.
     */
    public function page()
    {
        $balances = \App\Modules\Liquidity\Models\Balance::provider('b2c2')
            ->with('currencyInfo')
            ->orderBy('currency')
            ->get();

        return view('b2c2::balances', compact('balances'));
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
