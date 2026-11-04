<?php

namespace App\Modules\B2c2\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\B2c2\Services\InstrumentService;
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

    /**
     * Render the Instruments management page (local DB data).
     * Live syncing is triggered from the page via the `index` (live) endpoint.
     */
    public function page()
    {
        $instruments = \App\Modules\Liquidity\Models\Instrument::provider('b2c2')
            ->with(['fromCurrency', 'toCurrency'])
            ->orderBy('instrument')
            ->get();

        return view('b2c2::instruments', compact('instruments'));
    }

    /**
     * Toggle an instrument's user-controlled active status.
     * This flag is independent of the API and is never overwritten by sync.
     */
    public function toggleStatus($id)
    {
        $instrument = \App\Modules\Liquidity\Models\Instrument::provider('b2c2')->findOrFail($id);
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
