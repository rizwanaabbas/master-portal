<?php

namespace App\Modules\Liquidity\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Log;

class BalanceCurrencyResolver
{
    /** Common fiat tickers returned by liquidity providers. */
    protected array $fiatShorts = [
        'USD', 'EUR', 'GBP', 'CHF', 'CAD', 'AUD', 'JPY', 'SGD', 'HKD', 'NZD',
        'AED', 'MXN', 'TRY', 'PLN', 'SEK', 'NOK', 'DKK', 'ZAR', 'ILS',
    ];

    /**
     * Link a balance row to a portal currency, creating a disabled currency when missing.
     */
    public function resolveOrCreate(string $short): ?int
    {
        $short = strtoupper(trim($short));
        if ($short === '') {
            return null;
        }

        $existing = Currency::query()->where('short', $short)->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $isCrypto = !in_array($short, $this->fiatShorts, true);

        try {
            $currency = new Currency();
            $currency->short = $short;
            $currency->name = $short;
            $currency->is_crypto = $isCrypto ? 1 : 0;
            $currency->is_active = 0;
            $currency->min_amount = 0;
            $currency->max_amount = 0;
            $currency->decimals = $isCrypto ? 8 : 2;
            $currency->request_auto_approval = 0;
            $currency->save();

            Log::info('Liquidity balance sync created disabled currency', [
                'short' => $short,
                'is_crypto' => $isCrypto,
            ]);

            return (int) $currency->id;
        } catch (\Throwable $e) {
            Log::error('Liquidity balance sync failed to create currency', [
                'short' => $short,
                'error' => $e->getMessage(),
            ]);

            return Currency::query()->where('short', $short)->value('id');
        }
    }
}
