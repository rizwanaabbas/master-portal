<?php

namespace App\Modules\Liquidity\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Modules\Aquanow\Controllers\BalanceController as AquanowBalanceController;
use App\Modules\B2c2\Controllers\BalanceController as B2c2BalanceController;
use App\Modules\Liquidity\Models\Balance;
use App\Modules\Liquidity\Services\BalanceGridService;
use App\Modules\Liquidity\Services\ProviderResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

/**
 * Gateway for the Balances management page: one grid, scoped to whichever
 * provider (B2c2 or Aquanow) is currently enabled.
 */
class BalanceController extends Controller
{
    public function __construct(protected BalanceGridService $balanceGrid)
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

        $balanceCurrencies = Balance::query()
            ->provider($provider)
            ->orderBy('currency')
            ->pluck('currency')
            ->unique()
            ->values();

        $gridUrls = $this->defaultGridUrls($gridUrls);

        return view('liquidity::balances', compact('provider', 'providerLabel', 'activeCurrencies', 'balanceCurrencies', 'gridUrls'));
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

        $showAllAvailable = $request->boolean('show_all_available');

        $query = $this->balanceGrid->baseQuery($provider, $showAllAvailable);
        $this->balanceGrid->applyFilters($query, $request);

        return DataTables::of($query)
            ->addIndexColumn()
            ->filter(function (Builder $q) use ($request) {
                $this->balanceGrid->applySearch($q, $request->input('search.value'));
            })
            ->orderColumn('currency', 'liquidity_balances.currency $1')
            ->orderColumn('name', 'currencies.name $1')
            ->orderColumn('code', 'currencies.short $1')
            ->orderColumn('amount', 'liquidity_balances.amount $1')
            ->orderColumn('hold', 'liquidity_balances.hold $1')
            ->orderColumn('available', 'liquidity_balances.available $1')
            ->orderColumn('last_synced', 'liquidity_balances.updated_at $1')
            ->orderColumn('currency_status', 'currencies.is_active $1')
            ->addColumn('name', fn (Balance $row) => e(optional($row->currencyInfo)->name ?? '—'))
            ->addColumn('code', fn (Balance $row) => e(optional($row->currencyInfo)->short ?? $row->currency))
            ->addColumn('amount_fmt', fn (Balance $row) => BalanceGridService::formatDecimal($row->amount))
            ->addColumn('hold_fmt', fn (Balance $row) => BalanceGridService::formatDecimal($row->hold))
            ->addColumn('available_fmt', fn (Balance $row) => BalanceGridService::formatDecimal($row->available))
            ->addColumn('currency_status', function (Balance $row) use ($showAllAvailable) {
                if (!$showAllAvailable) {
                    return '';
                }

                $isActive = optional($row->currencyInfo)->is_active;

                return BalanceGridService::currencyStatusBadge(
                    $isActive === null ? null : (bool) $isActive
                );
            })
            ->addColumn('last_synced', function (Balance $row) {
                return $row->updated_at ? e($row->updated_at->format('Y-m-d H:i')) : '—';
            })
            ->rawColumns(['currency_status'])
            ->make(true);
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
            'data' => url('/liquidity/balances/data'),
            'sync' => url('/liquidity/balances/live'),
        ], $gridUrls ?? []);
    }

    public function sync(Request $request, ?string $forcedProvider = null)
    {
        $provider = $this->resolveProvider($forcedProvider);

        if (!$provider) {
            return response()->json([
                'success' => false,
                'message' => 'No liquidity provider is currently enabled.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $controller = $provider === ProviderResolver::B2C2
            ? app(B2c2BalanceController::class)
            : app(AquanowBalanceController::class);

        return $controller->index();
    }
}
