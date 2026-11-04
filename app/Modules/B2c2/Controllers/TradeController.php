<?php

namespace App\Modules\B2c2\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Modules\B2c2\Services\InstrumentService;
use App\Modules\Liquidity\Contracts\LiquidityProviderInterface;
use Illuminate\Http\Request;

/**
 * Renders the B2c2 Buy / Sell pages.
 *
 * This controller is intentionally thin: it only prepares the data required to
 * render the trade forms. The actual trading (quotes and order execution) is
 * handled by the existing RFQController / OrderController endpoints, which the
 * views call over AJAX. No trading business logic lives here.
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
        return 'b2c2';
    }

    public function buy(Request $request)
    {
        return view('b2c2::buy', $this->tradeData());
    }

    public function sell(Request $request)
    {
        return view('b2c2::sell', $this->tradeData());
    }

    /**
     * Shared payload for both trade pages.
     */
    private function tradeData(): array
    {
        // account_currency is needed so the view can resolve the instrument
        // from the selected crypto/fiat account pair.
        $cryptoAccounts = Account::where('is_active', true)
            ->where('is_crypto', true)
            ->select('id', 'title', 'account_number', 'account_currency')
            ->get();

        $fiatAccounts = Account::where('is_active', true)
            ->where('is_crypto', false)
            ->select('id', 'title', 'account_number', 'account_currency')
            ->get();

        // Lookup map of active instruments keyed by "<fromCurrencyId>-<toCurrencyId>"
        // so the view can pick the instrument for the chosen accounts.
        // from = crypto (base) currency, to = fiat (quote) currency.
        $activeInstruments = \App\Modules\Liquidity\Models\Instrument::provider('b2c2')
            ->where('is_active', true)
            ->whereNotNull('from_currency_id')
            ->whereNotNull('to_currency_id')
            ->get();

        $instrumentMap = $activeInstruments
            ->mapWithKeys(fn ($i) => [$i->from_currency_id . '-' . $i->to_currency_id => $i->instrument])
            ->toArray();

        // Trade constraints per instrument identifier, for display + input hints.
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
