<?php

namespace App\Modules\Aquanow\Services;

use App\Models\Account;
use App\Modules\Liquidity\Models\Instrument;
use App\Modules\Liquidity\Models\Rfq;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RFQService
{
    protected ClientService $client;

    public function __construct(ClientService $client)
    {
        $this->client = $client;
    }

    /**
     * Aquanow's RFQ flow is createQuote -> executeQuote (executeQuote happens
     * in OrderService::createOrder). createQuote already returns a price,
     * matching the single-call shape B2c2's request_for_quote/ gives us.
     */
    public function requestQuote(array $params): array
    {
        $side = $params['side'];
        $instrument = $params['instrument'];
        $quantity = (float) $params['quantity'];

        // Don't hit Aquanow at all for an instrument we don't have enabled —
        // fail fast with a generic message instead of a confusing API error.
        $cryptoCurrencyId = !empty($params['crypto_account_id']) ? Account::find($params['crypto_account_id'])?->account_currency : null;
        $fiatCurrencyId = !empty($params['fiat_account_id']) ? Account::find($params['fiat_account_id'])?->account_currency : null;

        if (!$this->instrumentAvailable($instrument, $cryptoCurrencyId, $fiatCurrencyId)) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Instrument not available.',
            ];
        }

        $payload = [
            'ticker'      => $instrument,
            'tradeSide'   => $side,
            'accountId'   => ConfigService::get()['credit_account_id'] ?? null,
            'usernameRef' => $params['client_rfq_id'] ?? (string) time(),
        ];

        // Aquanow's createQuote schema keys the trade amount differently per
        // side: `receiveQuantity` (crypto you want to receive) for buy,
        // `deliverQuantity` (crypto you're giving up) for sell — sending the
        // wrong key fails their anyOf schema validation. Must also be a JSON
        // number, not a string, or validation fails on type too.
        if ($side === 'buy') {
            $payload['receiveQuantity'] = $quantity;
        } else {
            $payload['deliverQuantity'] = $quantity;
        }

        $payload = array_filter($payload, fn ($value) => $value !== null);

        $rfq = Rfq::create([
            'provider'      => 'aquanow',
            'client_rfq_id' => $payload['usernameRef'],
            'instrument'    => $instrument,
            'side'          => $side,
            'quantity'      => $quantity,
            'status'        => 'pending',
            'created_by'    => Auth::id(),
            'raw_request'   => $payload,
        ]);

        $response = $this->client->post('trades/v1/createQuote', $payload);

        if ($response['success']) {
            $data = $response['data'];
            // Aquanow acks nest the actual quote fields under `payload`:
            // {"type":"rfqCreateQuoteAck","payload":{"quoteId","price","quoteTime","expireTime",...}}
            $quote = $data['payload'] ?? [];

            $rfq->update([
                'rfq_id'       => $quote['quoteId'] ?? null,
                'price'        => $quote['price'] ?? null,
                'status'       => 'quoted',
                // quoteTime/expireTime are epoch milliseconds, not ISO strings.
                'valid_until'  => $this->toAppTime($quote['expireTime'] ?? null),
                'created'      => $this->toAppTime($quote['quoteTime'] ?? null),
                'raw_response' => $data,
            ]);
        } else {
            $rfq->update([
                'status'       => 'failed',
                'raw_response' => $response['data'],
            ]);
        }

        return [
            'success' => $response['success'],
            'data' => $rfq->fresh(),
            'message' => $response['message'] ?? null,
        ];
    }

    /**
     * Aquanow timestamps are epoch milliseconds (e.g. 1657595867140), not
     * ISO strings — Carbon::parse() would misinterpret a raw integer.
     */
    protected function toAppTime($value): ?\Illuminate\Support\Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return \Illuminate\Support\Carbon::createFromTimestampMs((int) $value)
                    ->setTimezone(config('app.timezone'));
            }

            return \Illuminate\Support\Carbon::parse($value)->setTimezone(config('app.timezone'));
        } catch (\Exception $e) {
            Log::warning('Aquanow could not parse timestamp', ['value' => $value, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Whether this instrument is synced, active, and thus tradeable for
     * Aquanow — checked before hitting getQuote/createQuote or executeQuote
     * so an unavailable pair fails fast with a generic message rather than a
     * raw API error.
     *
     * The ticker itself never flips direction (XRP-USD covers both buy and
     * sell), but when we also know the two accounts' currencies, match them
     * against the instrument in either from/to order — the "from"/"to" the
     * pair happens to be synced as shouldn't matter for which side is
     * currently trading.
     */
    protected function instrumentAvailable(string $instrument, ?int $cryptoCurrencyId = null, ?int $fiatCurrencyId = null): bool
    {
        return Instrument::provider('aquanow')
            ->where('is_active', true)
            ->where(function ($query) use ($instrument, $cryptoCurrencyId, $fiatCurrencyId) {
                $query->where('instrument', $instrument);

                if ($cryptoCurrencyId && $fiatCurrencyId) {
                    $query->orWhere(fn ($q) => $q->forCurrencyPair($cryptoCurrencyId, $fiatCurrencyId));
                }
            })
            ->exists();
    }

    public function getLocalRfqs(array $filters = []): array
    {
        $query = Rfq::provider('aquanow');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['instrument'])) {
            $query->where('instrument', $filters['instrument']);
        }

        return [
            'success' => true,
            'data' => $query->orderBy('created_at', 'desc')->get(),
        ];
    }
}
