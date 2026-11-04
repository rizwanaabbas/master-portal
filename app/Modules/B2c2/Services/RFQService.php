<?php

namespace App\Modules\B2c2\Services;

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

    public function requestQuote(array $params): array
    {
        $payload = [
            'client_rfq_id' => $params['client_rfq_id'] ?? (string) time(),
            'quantity'      => $params['quantity'],
            'side'          => $params['side'],
            'instrument'    => $params['instrument'],
        ];

        $rfq = Rfq::create([
            'provider'      => 'b2c2',
            'client_rfq_id' => $payload['client_rfq_id'],
            'instrument'    => $payload['instrument'],
            'side'          => $payload['side'],
            'quantity'      => $payload['quantity'],
            'status'        => 'pending',
            'created_by'    => Auth::id(),
            'raw_request'   => $payload,
        ]);

        $response = $this->client->post('request_for_quote/', $payload);

        if ($response['success']) {
            $data = $response['data'];
            $rfq->update([
                'rfq_id'       => $data['rfq_id'] ?? null,
                'price'        => $data['price'] ?? null,
                'status'       => 'quoted',
                // B2c2 sends UTC timestamps (…Z). Convert to the app timezone so
                // the stored naive datetime represents the correct instant.
                'valid_until'  => $this->toAppTime($data['valid_until'] ?? null),
                'created'      => $this->toAppTime($data['created'] ?? null),
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
     * Parse a B2c2 UTC timestamp and convert it to the application timezone.
     */
    protected function toAppTime($value): ?\Illuminate\Support\Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->setTimezone(config('app.timezone'));
        } catch (\Exception $e) {
            Log::warning('B2c2 could not parse timestamp', ['value' => $value, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function getLocalRfqs(array $filters = []): array
    {
        $query = Rfq::provider('b2c2');

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
