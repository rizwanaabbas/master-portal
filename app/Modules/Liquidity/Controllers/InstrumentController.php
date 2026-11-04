<?php

namespace App\Modules\Liquidity\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Modules\Aquanow\Controllers\InstrumentController as AquanowInstrumentController;
use App\Modules\B2c2\Controllers\InstrumentController as B2c2InstrumentController;
use App\Modules\Liquidity\Models\Instrument;
use App\Modules\Liquidity\Services\InstrumentGridService;
use App\Modules\Liquidity\Services\ProviderResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

/**
 * Gateway for the Instruments management page: one grid, scoped to whichever
 * provider (B2c2 or Aquanow) is currently enabled — mirrors
 * Liquidity\Controllers\TradeController's delegation pattern.
 */
class InstrumentController extends Controller
{
    public function __construct(protected InstrumentGridService $instrumentGrid)
    {
    }

    public function page(?string $forcedProvider = null, ?array $gridUrls = null)
    {
        $provider = $this->resolveProvider($forcedProvider);

        abort_if(!$provider, Response::HTTP_SERVICE_UNAVAILABLE, 'No liquidity provider is currently enabled.');

        $providerLabel = $provider === ProviderResolver::B2C2 ? 'B2C2' : 'Aquanow';

        $activeCurrencies = Currency::query()
            ->where('is_active', true)
            ->orderBy('short')
            ->get(['short', 'name', 'is_crypto']);

        $instrumentTypes = Instrument::provider($provider)
            ->whereNotNull('instrument_type')
            ->where('instrument_type', '!=', '')
            ->distinct()
            ->orderBy('instrument_type')
            ->pluck('instrument_type');

        $gridUrls = $this->defaultGridUrls($gridUrls);

        return view('liquidity::instruments', compact('provider', 'providerLabel', 'activeCurrencies', 'instrumentTypes', 'gridUrls'));
    }

    public function data(Request $request, ?string $forcedProvider = null)
    {
        $provider = $this->resolveProvider($forcedProvider);

        if (!$provider) {
            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'No liquidity provider is currently enabled.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $query = $this->instrumentGrid->baseQuery($provider);
        $this->instrumentGrid->applyFilters($query, $request);

        $minTradeOrder = 'CAST(JSON_UNQUOTE(JSON_EXTRACT(liquidity_instruments.raw_response, "$.min_quantity_per_trade")) AS DECIMAL(24,8))';
        $maxTradeOrder = 'CAST(JSON_UNQUOTE(JSON_EXTRACT(liquidity_instruments.raw_response, "$.max_quantity_per_trade")) AS DECIMAL(24,8))';
        $priceSigOrder = 'CAST(JSON_UNQUOTE(JSON_EXTRACT(liquidity_instruments.raw_response, "$.price_significant_digits")) AS UNSIGNED)';

        return DataTables::of($query)
            ->addIndexColumn()
            ->filter(function (Builder $q) use ($request) {
                $this->instrumentGrid->applySearch($q, $request->input('search.value'));
            })
            ->orderColumn('instrument', 'liquidity_instruments.instrument $1')
            ->orderColumn('name', 'liquidity_instruments.name $1')
            ->orderColumn('instrument_type', 'liquidity_instruments.instrument_type $1')
            ->orderColumn('from_currency', 'from_cur.short $1')
            ->orderColumn('to_currency', 'to_cur.short $1')
            ->orderColumn('min_trade', $minTradeOrder . ' $1')
            ->orderColumn('max_trade', $maxTradeOrder . ' $1')
            ->orderColumn('price_sig', $priceSigOrder . ' $1')
            ->orderColumn('portal_status', 'liquidity_instruments.is_active $1')
            ->orderColumn('last_synced', 'liquidity_instruments.updated_at $1')
            ->addColumn('from_currency_label', function (Instrument $row) {
                $cur = $row->fromCurrency;
                if (!$cur) {
                    return '—';
                }

                return e($cur->name . ' (' . ($cur->short ?? '') . ')');
            })
            ->addColumn('to_currency_label', function (Instrument $row) {
                $cur = $row->toCurrency;
                if (!$cur) {
                    return '—';
                }

                return e($cur->name . ' (' . ($cur->short ?? '') . ')');
            })
            ->addColumn('min_trade_fmt', fn (Instrument $row) => InstrumentGridService::formatDecimal($row->min_quantity))
            ->addColumn('max_trade_fmt', fn (Instrument $row) => InstrumentGridService::formatDecimal($row->max_quantity))
            ->addColumn('price_sig_fmt', fn (Instrument $row) => $row->price_significant_digits ?? '—')
            ->addColumn('status_badge', function (Instrument $row) {
                $class = $row->is_active ? 'badge-success' : 'badge-secondary';
                $label = $row->is_active ? 'Active' : 'Inactive';

                return '<span class="badge ' . $class . '" id="status-' . (int) $row->id . '">' . e($label) . '</span>';
            })
            ->addColumn('action_btn', function (Instrument $row) {
                $id = (int) $row->id;
                $active = $row->is_active;
                $btnClass = $active ? 'btn-outline-danger' : 'btn-outline-success';
                $label = $active ? 'Disable' : 'Enable';

                return '<button type="button" class="btn btn-sm ' . $btnClass . '" id="toggle-' . $id . '" onclick="toggleStatus(' . $id . ')">' . e($label) . '</button>';
            })
            ->addColumn('last_synced', function (Instrument $row) {
                return $row->updated_at ? e($row->updated_at->format('Y-m-d H:i')) : '—';
            })
            ->rawColumns(['status_badge', 'action_btn'])
            ->make(true);
    }

    public function sync(Request $request, ?string $forcedProvider = null)
    {
        return $this->delegate($request, 'index', [], $forcedProvider);
    }

    public function toggleStatus(Request $request, $id, ?string $forcedProvider = null)
    {
        return $this->delegate($request, 'toggleStatus', [$id], $forcedProvider);
    }

    private function resolveProvider(?string $forcedProvider = null): ?string
    {
        if (in_array($forcedProvider, [ProviderResolver::B2C2, ProviderResolver::AQUANOW], true)) {
            return $forcedProvider;
        }

        return ProviderResolver::active();
    }

    private function defaultGridUrls(?array $gridUrls): array
    {
        return array_merge([
            'data' => url('/liquidity/instruments/data'),
            'sync' => url('/liquidity/instruments/live'),
            'togglePrefix' => url('/liquidity/instruments'),
        ], $gridUrls ?? []);
    }

    private function delegate(Request $request, string $method, array $params = [], ?string $forcedProvider = null)
    {
        $provider = $this->resolveProvider($forcedProvider);

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'No liquidity provider is currently enabled.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $controller = $provider === ProviderResolver::B2C2
            ? app(B2c2InstrumentController::class)
            : app(AquanowInstrumentController::class);

        return $controller->{$method}(...$params);
    }
}
