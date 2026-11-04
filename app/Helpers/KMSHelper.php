<?php
namespace App\Helpers;

use Aws\Kms\KmsClient;
use Illuminate\Support\Facades\Log;

class KMSHelper
{
    private const PLAINTEXT_LIMIT = 4096;

    /** AWS KMS Decrypt ciphertextBlob max size (bytes). */
    private const KMS_CIPHERTEXT_BLOB_MAX = 6144;

    public const ENVELOPE_PREFIX = 'env:';

    protected $kmsClient;
    protected $keyId;

    public function __construct()
    {
        $this->kmsClient = new KmsClient([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $this->keyId = env('AWS_KMS_KEY_ID');
    }

    /**
     * Encrypt data using AWS KMS (direct for <=4KB, AES-GCM envelope for larger payloads).
     */
    public function encrypt($plaintext)
    {
        try {
            $plainText = is_string($plaintext) ? $plaintext : (string) $plaintext;
            $plainLen = strlen($plainText);

            if ($plainLen <= self::PLAINTEXT_LIMIT) {
                $result = $this->kmsClient->encrypt([
                    'KeyId'     => $this->keyId,
                    'Plaintext' => $plainText,
                ]);

                return base64_encode($result['CiphertextBlob']);
            }

            return $this->encryptEnvelope($plainText);
        } catch (\Exception $e) {
            Log::error('KMS Encryption Error: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Decrypt data using AWS KMS (supports legacy direct blobs and envelope format).
     */
    public function decrypt($ciphertext)
    {
        try {
            if (is_string($ciphertext) && str_starts_with($ciphertext, self::ENVELOPE_PREFIX)) {
                return $this->decryptEnvelope(substr($ciphertext, strlen(self::ENVELOPE_PREFIX)));
            }

            $blob = base64_decode((string) $ciphertext, true);
            $blobLen = is_string($blob) ? strlen($blob) : 0;

            if ($blob === false || $blobLen === 0) {
                Log::warning('KMS decrypt skipped: invalid base64 ciphertext');

                return null;
            }

            if ($blobLen > self::KMS_CIPHERTEXT_BLOB_MAX) {
                Log::warning('KMS decrypt skipped: ciphertext blob exceeds AWS limit', [
                    'blob_len' => $blobLen,
                ]);

                return null;
            }

            $result = $this->kmsClient->decrypt([
                'CiphertextBlob' => $blob,
            ]);

            return $result['Plaintext'];
        } catch (\Exception $e) {
            Log::error('KMS Decryption Error: ' . $e->getMessage());

            return null;
        }
    }

    protected function encryptEnvelope(string $plaintext): string
    {
        $dek = random_bytes(32);
        $iv = random_bytes(12);
        $tag = '';
        $cipherText = openssl_encrypt($plaintext, 'aes-256-gcm', $dek, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipherText === false) {
            throw new \RuntimeException('AES envelope encryption failed.');
        }

        $encryptedDek = $this->kmsClient->encrypt([
            'KeyId' => $this->keyId,
            'Plaintext' => $dek,
        ]);

        $payload = json_encode([
            'ek' => base64_encode($encryptedDek['CiphertextBlob']),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct' => base64_encode($cipherText),
        ]);

        return self::ENVELOPE_PREFIX . base64_encode($payload);
    }

    protected function decryptEnvelope(string $encodedPayload): string
    {
        $payload = json_decode(base64_decode($encodedPayload), true);
        if (!is_array($payload) || empty($payload['ek']) || empty($payload['iv']) || empty($payload['tag']) || !isset($payload['ct'])) {
            throw new \RuntimeException('Invalid KMS envelope payload.');
        }

        $dekResult = $this->kmsClient->decrypt([
            'CiphertextBlob' => base64_decode($payload['ek']),
        ]);
        $dek = $dekResult['Plaintext'];

        $plain = openssl_decrypt(
            base64_decode($payload['ct']),
            'aes-256-gcm',
            $dek,
            OPENSSL_RAW_DATA,
            base64_decode($payload['iv']),
            base64_decode($payload['tag'])
        );

        if ($plain === false) {
            throw new \RuntimeException('AES envelope decryption failed.');
        }

        return $plain;
    }
}
