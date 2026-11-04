<?php

namespace App\Modules\B2c2\Services;

use App\Models\Currency;
use App\Modules\Liquidity\Models\Instrument;
use Illuminate\Support\Facades\Log;

class InstrumentService
{
    protected ClientService $client;

    public function __construct(ClientService $client)
    {
        $this->client = $client;
    }

    public function getInstruments(): array
    {
        $response = $this->client->get('instruments/');
       
        if ($response['success'] && is_array($response['data'])) {
            $this->syncInstruments($response['data']);
        }

        return $response;
    }

    public function getLocalInstruments(): array
    {
        return [
            'success' => true,
            'data' => Instrument::provider('b2c2')->orderBy('instrument')->get(),
        ];
    }

    protected function syncInstruments(array $instruments): void
    {
        foreach ($instruments as $item) {
            $data = is_array($item) ? $item : ['name' => $item];

            // The B2c2 API returns the instrument identifier in `name`
            // (e.g. "ETHUSC.SPOT"), the product type in `type`, and the
            // underlying asset in `underlier`.
            $instrument = $data['name'] ?? $data['instrument'] ?? null;

            if (!$instrument) {
                continue;
            }

            try {
                [$fromCode, $toCode] = $this->splitPair($data['underlier'] ?? $instrument);

                Instrument::updateOrCreate(
                    ['provider' => 'b2c2', 'instrument' => $instrument],
                    [
                        'name'             => $data['underlier'] ?? $instrument,
                        'instrument_type'  => $data['type'] ?? $data['instrument_type'] ?? null,
                        'from_currency_id' => $this->resolveCurrencyId($fromCode),
                        'to_currency_id'   => $this->resolveCurrencyId($toCode),
                        'raw_response'     => $data,
                    ]
                );
            } catch (\Exception $e) {
                Log::error('B2c2 failed to sync instrument', [
                    'instrument' => $instrument,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Split an instrument underlier into its [from, to] currency codes.
     *
     * The underlier bundles base + quote (e.g. "ADAUSC" = ADA -> USC).
     * The quote is one of B2c2's known settlement currencies, matched as a
     * suffix; the remainder is the base asset.
     */
    protected function splitPair(string $underlier): array
    {
        $underlier = explode('.', $underlier)[0];

        // Longest suffixes first so USDT wins over USD, USC over USD, etc.
        $quotes = ['USDT', 'USDC', 'USC', 'USD', 'EUR', 'GBP', 'JPY', 'CHF', 'BTC', 'ETH'];

        foreach ($quotes as $quote) {
            if (str_ends_with($underlier, $quote) && strlen($underlier) > strlen($quote)) {
                return [substr($underlier, 0, -strlen($quote)), $quote];
            }
        }

        // Unknown quote — treat the whole thing as the base asset.
        return [$underlier, null];
    }

    /**
     * Resolve a local Currency id from a currency code (matched on `short`).
     */
    protected function resolveCurrencyId(?string $code): ?int
    {
        if ($code === null || $code === '') {
            return null;
        }

        return Currency::where('short', $code)->value('id');
    }
}
