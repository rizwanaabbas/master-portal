<?php

namespace App\Modules\B2c2\Services;

use App\Modules\Liquidity\Models\Balance;
use App\Modules\Liquidity\Services\BalanceCurrencyResolver;
use Illuminate\Support\Facades\Log;

class BalanceService
{
    protected ClientService $client;

    public function __construct(ClientService $client, protected BalanceCurrencyResolver $currencyResolver)
    {
        $this->client = $client;
    }

    public function getBalances(): array
    {
        $response = $this->client->get('balance/');
        //dd($response);
        if ($response['success'] && is_array($response['data'])) {
            $this->syncBalances($response['data']);
        }

        return $response;
    }

    public function getBalanceBreakdown(): array
    {
        return $this->client->get('balance/breakdown/');
    }

    public function getLocalBalances(): array
    {
        return [
            'success' => true,
            'data' => Balance::provider('b2c2')->orderBy('currency')->get(),
        ];
    }

    protected function syncBalances(array $balances): void
    {
        // The B2c2 balance API returns an associative map of
        // currency => amount, e.g. ["AAV" => "0", "BTC" => "1.25", ...].
        foreach ($balances as $currency => $amount) {
            if (!is_string($currency) || $currency === '') {
                continue;
            }

            try {
                Balance::updateOrCreate(
                    ['provider' => 'b2c2', 'currency' => $currency],
                    [
                        'currency_id'  => $this->resolveCurrencyId($currency),
                        'amount'       => is_array($amount) ? ($amount['amount'] ?? 0) : $amount,
                        'hold'         => is_array($amount) ? ($amount['hold'] ?? 0) : 0,
                        'available'    => is_array($amount) ? ($amount['available'] ?? $amount['amount'] ?? 0) : $amount,
                        'raw_response' => is_array($amount) ? $amount : ['amount' => $amount],
                    ]
                );
            } catch (\Exception $e) {
                Log::error('B2c2 failed to sync balance', [
                    'currency' => $currency,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Resolve the local Currency id from a B2c2 currency code (matched on `short`).
     */
    protected function resolveCurrencyId(string $currency): ?int
    {
        return $this->currencyResolver->resolveOrCreate($currency);
    }
}
