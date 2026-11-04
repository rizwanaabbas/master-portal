<?php

namespace App\Modules\Aquanow\Services;

use App\Modules\Aquanow\Helpers\SignatureHelper;
use App\Modules\Liquidity\Models\TransactionLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class ClientService
{
    protected ?Client $httpClient = null;
    protected ?Client $marketDataClient = null;
    protected ?array $config = null;

    /**
     * Config is resolved lazily on first actual API call, not in the
     * constructor — see App\Modules\B2c2\Services\ClientService for why
     * (route:list / any container resolution of ClientService must not
     * throw just because the module is disabled).
     */
    protected function config(): array
    {
        return $this->config ??= ConfigService::get();
    }

    protected function client(): Client
    {
        if ($this->httpClient === null) {
            $config = $this->config();

            $this->httpClient = new Client([
                'base_uri'        => rtrim($config['base_url'], '/') . '/',
                'timeout'         => $config['timeout'],
                'connect_timeout' => $config['connect_timeout'],
                'headers'         => array_merge($config['headers'], [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ]),
            ]);
        }

        return $this->httpClient;
    }

    /**
     * Market data (available symbols) lives on a separate host per the
     * Aquanow API reference and is unauthenticated.
     */
    protected function marketDataClient(): Client
    {
        if ($this->marketDataClient === null) {
            $config = $this->config();

            $this->marketDataClient = new Client([
                'base_uri'        => rtrim($config['market_data_url'], '/') . '/',
                'timeout'         => $config['timeout'],
                'connect_timeout' => $config['connect_timeout'],
                'headers'         => ['Accept' => 'application/json'],
            ]);
        }

        return $this->marketDataClient;
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    public function delete(string $endpoint, array $data = []): array
    {
        return $this->request('DELETE', $endpoint, ['json' => $data]);
    }

    /**
     * Unauthenticated market-data call (e.g. availablesymbols).
     */
    public function getMarketData(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query], $this->marketDataClient(), true);
    }

    protected function request(string $method, string $endpoint, array $options = [], ?Client $client = null, bool $unauthenticated = false): array
    {
        $client = $client ?? $this->client();
        $path = ltrim($endpoint, '/');
        $start = microtime(true);

        if (!$unauthenticated) {
            $options['headers'] = array_merge(
                $options['headers'] ?? [],
                SignatureHelper::headers($this->config()['api_key'], $this->config()['api_secret'], $method, '/' . $path)
            );
        }

        try {
            $response = $client->request($method, $path, $options);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            $data = json_decode($body, true) ?? ['raw' => $body];

            // Aquanow returns business errors (insufficient funds, expired
            // quote, ...) as HTTP 200 with an `error` key in the body rather
            // than a 4xx/5xx status — an ack-style envelope
            // ({"type":"...Ack","error":{"message":"..."},"payload":{...}}).
            // A 2xx alone does not mean success; the body must be inspected.
            if (isset($data['error'])) {
                $message = is_array($data['error'])
                    ? ($data['error']['message'] ?? 'Aquanow returned an error.')
                    : (string) $data['error'];

                $this->log($method, $path, $options, $data, $statusCode, $message, microtime(true) - $start);

                return [
                    'success' => false,
                    'data' => $data,
                    'status' => $statusCode,
                    'message' => $message,
                ];
            }

            $this->log($method, $path, $options, $data, $statusCode, null, microtime(true) - $start);

            return [
                'success' => true,
                'data' => $data,
                'status' => $statusCode,
            ];
        } catch (ClientException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 400;
            $body = $e->getResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage();
            $data = json_decode($body, true) ?? ['error' => $body];

            $this->log($method, $path, $options, $data, $statusCode, $e->getMessage(), microtime(true) - $start);

            Log::error('Aquanow API client error', [
                'endpoint' => $path,
                'status' => $statusCode,
                'response' => $data,
            ]);

            return [
                'success' => false,
                'data' => $data,
                'status' => $statusCode,
                'message' => $this->extractErrorMessage($data, 'Client error (HTTP ' . $statusCode . ')'),
            ];
        } catch (ServerException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 500;
            $body = $e->getResponse() ? (string) $e->getResponse()->getBody() : $e->getMessage();
            $data = json_decode($body, true) ?? ['error' => $body];

            $this->log($method, $path, $options, $data, $statusCode, $e->getMessage(), microtime(true) - $start);

            Log::error('Aquanow API server error', [
                'endpoint' => $path,
                'status' => $statusCode,
                'response' => $data,
            ]);

            return [
                'success' => false,
                'data' => $data,
                'status' => $statusCode,
                'message' => $this->extractErrorMessage($data, 'Aquanow server error (HTTP ' . $statusCode . ')'),
            ];
        } catch (ConnectException $e) {
            $this->log($method, $path, $options, ['error' => $e->getMessage()], 0, $e->getMessage(), microtime(true) - $start);

            Log::error('Aquanow API connection error', [
                'endpoint' => $path,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'status' => 0,
                'message' => 'Could not connect to Aquanow: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $this->log($method, $path, $options, ['error' => $e->getMessage()], 0, $e->getMessage(), microtime(true) - $start);

            Log::error('Aquanow API unexpected error', [
                'endpoint' => $path,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build a human-readable message from an Aquanow error payload.
     */
    protected function extractErrorMessage(mixed $data, string $fallback): string
    {
        if (!is_array($data)) {
            return is_string($data) && $data !== '' ? $data : $fallback;
        }

        foreach (['message', 'error', 'detail'] as $key) {
            if (!empty($data[$key]) && is_string($data[$key])) {
                return $data[$key];
            }
        }

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json && $json !== '[]' && $json !== '{}' ? $fallback . ': ' . $json : $fallback;
    }

    protected function log(string $method, string $endpoint, array $options, mixed $response, int $statusCode, ?string $error, float $duration): void
    {
        try {
            TransactionLog::create([
                'provider'         => 'aquanow',
                'endpoint'         => $endpoint,
                'method'           => $method,
                'request_payload'  => $options,
                'response_payload' => $response,
                'response_status'  => $statusCode,
                'status'           => $error ? 'failed' : 'success',
                'error_message'    => $error,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log Aquanow transaction', ['error' => $e->getMessage()]);
        }
    }
}
