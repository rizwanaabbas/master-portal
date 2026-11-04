<?php

namespace App\Modules\B2c2\Services;

use App\Modules\Liquidity\Models\TransactionLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class ClientService
{
    protected ?Client $httpClient = null;
    protected ?array $config = null;

    /**
     * Config is resolved lazily on first actual API call, not in the
     * constructor. Building this eagerly would mean any code path that merely
     * instantiates ClientService (e.g. Laravel resolving a controller's
     * constructor dependencies for `route:list`) throws if the B2c2 module
     * happens to be disabled, even though no request was ever made.
     */
    protected function client(): Client
    {
        if ($this->httpClient === null) {
            $this->config = ConfigService::get();

            $this->httpClient = new Client([
                'base_uri'        => rtrim($this->config['base_url'], '/') . '/',
                'timeout'         => $this->config['timeout'],
                'connect_timeout' => $this->config['connect_timeout'],
                'headers'         => array_merge($this->config['headers'], [
                    'Authorization' => 'Token ' . $this->config['api_token'],
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ]),
            ]);
        }

        return $this->httpClient;
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    protected function request(string $method, string $endpoint, array $options = []): array
    {
        $path = ltrim($endpoint, '/');
        $start = microtime(true);

        try {
            $response = $this->client()->request($method, $path, $options);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            $data = json_decode($body, true) ?? ['raw' => $body];

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

            Log::error('B2c2 API client error', [
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

            Log::error('B2c2 API server error', [
                'endpoint' => $path,
                'status' => $statusCode,
                'response' => $data,
            ]);

            return [
                'success' => false,
                'data' => $data,
                'status' => $statusCode,
                'message' => $this->extractErrorMessage($data, 'B2c2 server error (HTTP ' . $statusCode . ')'),
            ];
        } catch (ConnectException $e) {
            $this->log($method, $path, $options, ['error' => $e->getMessage()], 0, $e->getMessage(), microtime(true) - $start);

            Log::error('B2c2 API connection error', [
                'endpoint' => $path,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'status' => 0,
                'message' => 'Could not connect to B2c2: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $this->log($method, $path, $options, ['error' => $e->getMessage()], 0, $e->getMessage(), microtime(true) - $start);

            Log::error('B2c2 API unexpected error', [
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
     * Build a human-readable message from a B2c2 error payload.
     *
     * B2c2 returns errors in several shapes, e.g.:
     *   {"errors": [{"message": "...", "code": 1234, "field": "instrument"}]}
     *   {"errors": {"instrument": ["is required"]}}
     *   {"error_message": "..."} / {"detail": "..."} / {"message": "..."}
     */
    protected function extractErrorMessage(mixed $data, string $fallback): string
    {
        if (!is_array($data)) {
            return is_string($data) && $data !== '' ? $data : $fallback;
        }

        // Single-message keys first.
        foreach (['error_message', 'message', 'detail', 'error'] as $key) {
            if (!empty($data[$key]) && is_string($data[$key])) {
                return $data[$key];
            }
        }

        // `errors` can be a list of error objects or a field => messages map.
        if (!empty($data['errors'])) {
            $messages = [];

            foreach ((array) $data['errors'] as $field => $error) {
                if (is_array($error)) {
                    // Either an error object (has 'message') or a list of strings.
                    if (isset($error['message'])) {
                        $prefix = isset($error['field']) ? $error['field'] . ': ' : '';
                        $messages[] = $prefix . $error['message'];
                    } else {
                        foreach ($error as $msg) {
                            if (is_string($msg)) {
                                $messages[] = (is_string($field) ? $field . ': ' : '') . $msg;
                            }
                        }
                    }
                } elseif (is_string($error)) {
                    $messages[] = (is_string($field) ? $field . ': ' : '') . $error;
                }
            }

            if (!empty($messages)) {
                return implode(' | ', $messages);
            }
        }

        // Last resort: compact JSON of the payload so nothing is hidden.
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json && $json !== '[]' && $json !== '{}' ? $fallback . ': ' . $json : $fallback;
    }

    protected function log(string $method, string $endpoint, array $options, mixed $response, int $statusCode, ?string $error, float $duration): void
    {
        try {
            TransactionLog::create([
                'provider'         => 'b2c2',
                'endpoint'         => $endpoint,
                'method'           => $method,
                'request_payload'  => $options,
                'response_payload' => $response,
                'response_status'  => $statusCode,
                'status'           => $error ? 'failed' : 'success',
                'error_message'    => $error,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log B2c2 transaction', ['error' => $e->getMessage()]);
        }
    }
}
