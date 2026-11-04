<?php

namespace App\Modules\Liquidity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Aquanow\Controllers\TradeController as AquanowTradeController;
use App\Modules\B2c2\Controllers\TradeController as B2c2TradeController;
use App\Modules\Liquidity\Services\ProviderResolver;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gateway that renders the Buy/Sell pages for whichever liquidity provider
 * is currently enabled, delegating to that provider's own thin TradeController.
 */
class TradeController extends Controller
{
    public function buy(Request $request)
    {
        return $this->delegate($request, 'buy');
    }

    public function sell(Request $request)
    {
        return $this->delegate($request, 'sell');
    }

    private function delegate(Request $request, string $method)
    {
        $provider = ProviderResolver::active();

        abort_if(!$provider, Response::HTTP_SERVICE_UNAVAILABLE, 'No liquidity provider is currently enabled.');

        $controller = $provider === ProviderResolver::B2C2
            ? app(B2c2TradeController::class)
            : app(AquanowTradeController::class);

        return $controller->{$method}($request);
    }
}
