<?php

namespace App\Modules\Aquanow\Services;

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
        $accountId = ConfigService::get()['credit_account_id'] ?? null;

        $response = $this->client->get('users/v1/userbalance', array_filter([
            'accountId' => $accountId,
        ]));

        if ($response['success'] && is_array($response['data'])) {
            $data = $response['data'];

            // A response scoped to a single account/symbol comes back as one
            // balance object (assoc array with a 'symbol' key) rather than a
            // list — normalise it to a list so syncBalances() can handle both.
            if (isset($data['symbol'])) {
                $data = [$data];
            }

            $this->syncBalances($data);
        }

        return $response;
    }

    public function getBalanceBreakdown(): array
    {
        return $this->client->get('users/v1/totalUB');
    }

    public function getLocalBalances(): array
    {
        return [
            'success' => true,
            'data' => Balance::provider('aquanow')->orderBy('currency')->get(),
        ];
    }

    /**
     * Aquanow's userbalance endpoint returns a list of per-symbol balance
     * objects, e.g.:
     *   {"symbol":"BTC","totalBalance":2.78,"available":2.46,"pending":0.32,
     *    "availableBalance":2.46,"pendingTransfer":0.32,"updatedAt":...}
     * (per Aquanow's own Postman collection response examples — note there is
     * no "balance"/"hold" key, those were an incorrect earlier assumption).
     */
    protected function syncBalances(array $balances): void
    {
        foreach ($balances as $item) {
            if (!is_array($item) || empty($item['symbol'])) {
                continue;
            }

            $currency = $item['symbol'];

            try {
                Balance::updateOrCreate(
                    ['provider' => 'aquanow', 'currency' => $currency],
                    [
                        'currency_id'  => $this->resolveCurrencyId($currency),
                        'amount'       => $item['totalBalance'] ?? 0,
                        'hold'         => $item['pending'] ?? 0,
                        'available'    => $item['available'] ?? $item['totalBalance'] ?? 0,
                        'raw_response' => $item,
                    ]
                );
            } catch (\Exception $e) {
                Log::error('Aquanow failed to sync balance', [
                    'currency' => $currency,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function resolveCurrencyId(string $currency): ?int
    {
        return $this->currencyResolver->resolveOrCreate($currency);
    }
}
