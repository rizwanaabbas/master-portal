<?php

namespace App\Modules\Aquanow\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Modules\Aquanow\Services\InstrumentService;
use App\Modules\Liquidity\Contracts\LiquidityProviderInterface;
use App\Modules\Liquidity\Models\Instrument;
use Illuminate\Http\Request;

/**
 * Renders the Aquanow Buy / Sell pages.
 *
 * Mirrors App\Modules\B2c2\Controllers\TradeController: thin controller, no
 * trading business logic — the views call RFQController/OrderController over AJAX.
 */
class TradeController extends Controller implements LiquidityProviderInterface
{
    protected InstrumentService $instrumentService;

    public function __construct(InstrumentService $instrumentService)
    {
        $this->instrumentService = $instrumentService;
    }

    public function providerName(): string
    {
        return 'aquanow';
    }

    public function buy(Request $request)
    {
        return view('aquanow::buy', $this->tradeData());
    }

    public function sell(Request $request)
    {
        return view('aquanow::sell', $this->tradeData());
    }

    private function tradeData(): array
    {
        $cryptoAccounts = Account::where('is_active', true)
            ->where('is_crypto', true)
            ->select('id', 'title', 'account_number', 'account_currency')
            ->get();

        $fiatAccounts = Account::where('is_active', true)
            ->where('is_crypto', false)
            ->select('id', 'title', 'account_number', 'account_currency')
            ->get();

        $activeInstruments = Instrument::provider('aquanow')
            ->where('is_active', true)
            ->whereNotNull('from_currency_id')
            ->whereNotNull('to_currency_id')
            ->get();

        $instrumentMap = $activeInstruments
            ->mapWithKeys(fn ($i) => [$i->from_currency_id . '-' . $i->to_currency_id => $i->instrument])
            ->toArray();

        $instrumentMeta = $activeInstruments
            ->mapWithKeys(fn ($i) => [$i->instrument => [
                'min_quantity'             => $i->min_quantity,
                'max_quantity'             => $i->max_quantity,
                'price_significant_digits' => $i->price_significant_digits,
            ]])
            ->toArray();

        return compact('cryptoAccounts', 'fiatAccounts', 'instrumentMap', 'instrumentMeta');
    }
}
