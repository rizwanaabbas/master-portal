<?php

namespace App\Modules\Liquidity\Contracts;

use Illuminate\Http\Request;

/**
 * Contract implemented by each provider's own TradeController (B2c2, Aquanow)
 * so the Liquidity gateway can delegate Buy/Sell rendering without knowing
 * which provider is active.
 */
interface LiquidityProviderInterface
{
    /** Machine name matching the `provider` column / Module name, e.g. 'b2c2', 'aquanow'. */
    public function providerName(): string;

    public function buy(Request $request);

    public function sell(Request $request);
}
