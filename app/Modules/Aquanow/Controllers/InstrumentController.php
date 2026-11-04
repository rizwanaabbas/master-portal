<?php

namespace App\Modules\Aquanow\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Aquanow\Services\InstrumentService;
use App\Modules\Liquidity\Controllers\InstrumentController as LiquidityInstrumentController;
use App\Modules\Liquidity\Models\Instrument;
use App\Modules\Liquidity\Services\ProviderResolver;
use Illuminate\Http\Request;

class InstrumentController extends Controller
{
    protected InstrumentService $instrumentService;

    public function __construct(InstrumentService $instrumentService)
    {
        $this->instrumentService = $instrumentService;
    }

    public function index()
    {
        try {
            $result = $this->instrumentService->getInstruments();

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to fetch instruments.',
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
        return app(LiquidityInstrumentController::class)->page(ProviderResolver::AQUANOW, [
            'data' => url('/aquanow/instruments/data'),
            'sync' => url('/aquanow/instruments/live'),
            'togglePrefix' => url('/aquanow/instruments'),
        ]);
    }

    public function gridData(Request $request)
    {
        return app(LiquidityInstrumentController::class)->data($request, ProviderResolver::AQUANOW);
    }

    public function toggleStatus($id)
    {
        $instrument = Instrument::provider('aquanow')->findOrFail($id);
        $instrument->is_active = !$instrument->is_active;
        $instrument->save();

        return response()->json([
            'success'   => true,
            'is_active' => $instrument->is_active,
            'message'   => 'Instrument ' . ($instrument->is_active ? 'enabled' : 'disabled') . '.',
        ]);
    }

    public function local()
    {
        try {
            $result = $this->instrumentService->getLocalInstruments();

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
