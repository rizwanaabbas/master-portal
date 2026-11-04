<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [60, 120, 240, 480];

    protected $webhookUrl;
    protected $data;

    public function __construct($webhookUrl, $data)
    {
        $this->webhookUrl = $webhookUrl;
        $this->data = $data;
        $queuePrefix = env('QUEUE_PREFIX');

        $this->onQueue($queuePrefix);
    }

    public function handle()
    {
        $url = trim((string) $this->webhookUrl);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            Log::warning('SendWebhookNotification skipped: invalid merchant webhook URL', [
                'url' => $this->webhookUrl,
                'request_id' => $this->data['request_id'] ?? null,
            ]);

            return;
        }

        Log::info('SendWebhookNotification posting', [
            'url' => $url,
            'request_id' => $this->data['request_id'] ?? null,
            'request_type' => $this->data['request_type'] ?? null,
        ]);

        try {
            $response = Http::timeout(30)->post($url, $this->data);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Merchant webhook HTTP error: ' . $e->getMessage() . ' (url=' . $url . ')',
                0,
                $e
            );
        }

        if ($response->failed()) {
            throw new \RuntimeException(sprintf(
                'Merchant webhook returned HTTP %s (url=%s, body=%s)',
                $response->status(),
                $url,
                mb_substr((string) $response->body(), 0, 500)
            ));
        }
    }

    // public function backoff()
    // {
    //     return [3, 4, 5, 6];
    // }

    public function retryUntil()
    {
        return now()->addMinutes(20);
    }

    public function failed(\Throwable $e)
    {
        Log::error('SendWebhookNotification failed after maximum retries', [
            'url' => $this->webhookUrl,
            'request_id' => $this->data['request_id'] ?? null,
            'error' => $e->getMessage(),
        ]);
    }
}
