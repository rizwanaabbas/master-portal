<?php

namespace App\Modules\Fireblocks\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Fireblocks\Services\FireblocksConfigService;
use App\Modules\Fireblocks\Services\WebhookService;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * @var WebhookService
     */
    protected $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Extract the Fireblocks V2 detached JWS signature from the request.
     */
    protected function extractSignature(Request $request): ?string
    {
        $signature = $request->header('Fireblocks-Webhook-Signature');

        return is_string($signature) && $signature !== '' ? $signature : null;
    }

    /**
     * Handle incoming Fireblocks webhook events.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $signature = $this->extractSignature($request);
        $rawBody = $request->getContent();

        Log::debug('Fireblocks webhook received', [
            'signature_present' => !empty($signature),
            'payload_length'    => strlen($rawBody),
        ]);

        if (empty($signature)) {
            Log::warning('Fireblocks webhook received without signature');
            return response()->json(['error' => 'Missing signature'], 401);
        }

        if ($this->shouldVerifySignature()) {
            $isValid = $this->webhookService->validateSignature($rawBody, $signature);

            if (!$isValid) {
                Log::warning('Fireblocks webhook signature validation failed');
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        } else {
            Log::warning('Fireblocks webhook signature validation bypassed for testing');
        }

        $payload = json_decode($rawBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Fireblocks webhook invalid JSON payload', [
                'error' => json_last_error_msg(),
            ]);
            return response()->json(['error' => 'Invalid JSON payload'], 400);
        }

        $result = $this->webhookService->handleEvent($payload);
       // dd($result);
        
        if (!$result['success']) {
            Log::error('Fireblocks webhook processing failed', [
                'message' => $result['message'],
            ]);
            return response()->json(['error' => $result['message']], 400);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    private function shouldVerifySignature(): bool
    {
        try {
            return (bool) (FireblocksConfigService::get()['webhook_verify_signature'] ?? true);
        } catch (\Throwable $e) {
            return true;
        }
    }
}
