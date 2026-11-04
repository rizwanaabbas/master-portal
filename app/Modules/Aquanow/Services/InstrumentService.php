<?php

namespace App\Modules\Aquanow\Services;

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
        $response = $this->client->getMarketData('availablesymbols');

        if ($response['success'] && is_array($response['data'])) {
            $this->syncInstruments($response['data']);
        }

        return $response;
    }

    public function getLocalInstruments(): array
    {
        return [
            'success' => true,
            'data' => Instrument::provider('aquanow')->orderBy('instrument')->get(),
        ];
    }

    /**
     * Aquanow tickers are "BASE-QUOTE" pairs (e.g. "BTC-USD"), unlike B2c2's
     * bundled underlier codes — no suffix-splitting needed.
     */
    protected function syncInstruments(array $instruments): void
    {
        foreach ($instruments as $item) {
            $data = is_array($item) ? $item : ['ticker' => $item];

            $instrument = $data['ticker'] ?? $data['symbol'] ?? null;

            if (!$instrument) {
                continue;
            }

            try {
                [$fromCode, $toCode] = $this->splitPair($instrument);

                Instrument::updateOrCreate(
                    ['provider' => 'aquanow', 'instrument' => $instrument],
                    [
                        'name'             => $instrument,
                        'instrument_type'  => $data['type'] ?? null,
                        'from_currency_id' => $this->resolveCurrencyId($fromCode),
                        'to_currency_id'   => $this->resolveCurrencyId($toCode),
                        'raw_response'     => $data,
                    ]
                );
            } catch (\Exception $e) {
                Log::error('Aquanow failed to sync instrument', [
                    'instrument' => $instrument,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function splitPair(string $ticker): array
    {
        $parts = explode('-', $ticker);

        return [$parts[0] ?? $ticker, $parts[1] ?? null];
    }

    protected function resolveCurrencyId(?string $code): ?int
    {
        if ($code === null || $code === '') {
            return null;
        }

        return Currency::where('short', $code)->value('id');
    }
}
