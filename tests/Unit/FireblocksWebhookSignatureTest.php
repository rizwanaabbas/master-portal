<?php

namespace Tests\Unit;

use App\Modules\Fireblocks\Controllers\WebhookController;
use App\Modules\Fireblocks\Services\WebhookService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class FireblocksWebhookSignatureTest extends TestCase
{
    private string $privateKey;
    private string $publicKey;
    private WebhookService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($key, $privateKey);
        $this->privateKey = $privateKey;
        $this->publicKey = openssl_pkey_get_details($key)['key'];
        $this->service = new TestableWebhookService($this->publicKey);
    }

    public function test_it_verifies_a_valid_v2_detached_jws(): void
    {
        $payload = '{"eventType":"transaction.status.updated","data":{"id":"tx-1"}}';

        $this->assertTrue(
            $this->service->validateSignature($payload, $this->sign($payload))
        );
    }

    public function test_it_rejects_a_signature_for_a_modified_payload(): void
    {
        $payload = '{"eventType":"transaction.status.updated","data":{"id":"tx-1"}}';

        $this->assertFalse(
            $this->service->validateSignature($payload . ' ', $this->sign($payload))
        );
    }

    public function test_it_rejects_legacy_v1_signatures(): void
    {
        openssl_sign('payload', $signature, $this->privateKey, OPENSSL_ALGO_SHA512);

        $this->assertFalse(
            $this->service->validateSignature('payload', base64_encode($signature))
        );
    }

    public function test_it_rejects_non_rs512_jws_algorithms(): void
    {
        $payload = '{"eventType":"transaction.status.updated"}';

        $this->assertFalse(
            $this->service->validateSignature($payload, $this->sign($payload, 'RS256'))
        );
    }

    public function test_controller_rejects_legacy_header_without_falling_back(): void
    {
        $service = Mockery::mock(WebhookService::class);
        $service->shouldNotReceive('validateSignature');

        $request = Request::create(
            '/fireblocks/webhook',
            'POST',
            [],
            [],
            [],
            ['HTTP_FIREBLOCKS_SIGNATURE' => 'legacy-signature'],
            '{"eventType":"transaction.status.updated"}'
        );

        $response = (new WebhookController($service))->handle($request);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['error' => 'Missing signature'], $response->getData(true));
    }

    public function test_it_uses_the_env_backed_config_for_the_jwks_environment(): void
    {
        config()->set('fireblocks.webhook_public_key_type', 'production');

        $this->assertSame(
            'production',
            (new EnvironmentAwareWebhookService())->configuredJwksEnvironment()
        );
    }

    public function test_it_rejects_an_invalid_jwks_environment(): void
    {
        config()->set('fireblocks.webhook_public_key_type', 'staging');

        $this->assertNull(
            (new EnvironmentAwareWebhookService())->configuredJwksEnvironment()
        );
    }

    private function sign(string $payload, string $algorithm = 'RS512'): string
    {
        $header = $this->base64urlEncode(json_encode([
            'alg' => $algorithm,
            'kid' => 'test-key',
        ]));
        $signingInput = $header . '.' . $this->base64urlEncode($payload);

        openssl_sign($signingInput, $signature, $this->privateKey, OPENSSL_ALGO_SHA512);

        return $header . '..' . $this->base64urlEncode($signature);
    }

    private function base64urlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

class TestableWebhookService extends WebhookService
{
    private string $publicKey;

    public function __construct(string $publicKey)
    {
        $this->publicKey = $publicKey;
    }

    protected function resolveJwksPublicKey(string $kid): ?string
    {
        return $kid === 'test-key' ? $this->publicKey : null;
    }
}

class EnvironmentAwareWebhookService extends WebhookService
{
    public function configuredJwksEnvironment(): ?string
    {
        return $this->resolveJwksEnvironment();
    }
}
