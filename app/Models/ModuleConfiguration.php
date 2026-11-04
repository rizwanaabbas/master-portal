<?php

namespace App\Models;

use App\Helpers\KMSHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ModuleConfiguration extends Model
{
    use HasFactory;

    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function updater()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function module()
    {
        return $this->hasOne(Module::class, 'id', 'module_id');
    }

    /**
     * Encrypt configurations before saving to the database.
     */
    public function setConfigurationsAttribute($value)
    {
        if (!$value) {
            return;
        }

        $plainLen = strlen((string) $value);
        $kmsHelper = new KMSHelper();
        $encrypted = $kmsHelper->encrypt($value);

        if (!empty($encrypted)) {
            $this->attributes['configurations'] = $encrypted;

            return;
        }

        Log::warning('Module configuration KMS encrypt failed; storing plaintext serialized payload', [
            'module_configuration_id' => $this->id ?? null,
            'plain_len' => $plainLen,
        ]);

        $this->attributes['configurations'] = $value;
    }

    /**
     * Decrypt configurations when retrieving from the database.
     */
    public function getConfigurationsAttribute($value)
    {
        if (!$value) {
            return null;
        }

        if ($this->isPlainSerializedConfiguration($value)) {
            return $value;
        }

        if (str_starts_with((string) $value, KMSHelper::ENVELOPE_PREFIX)) {
            return (new KMSHelper())->decrypt($value);
        }

        $decrypted = (new KMSHelper())->decrypt($value);

        if ($decrypted !== null && $decrypted !== '') {
            return $decrypted;
        }

        if ($this->isPlainSerializedConfiguration($value)) {
            return $value;
        }

        return $decrypted;
    }

    /**
     * Plain PHP-serialized config at rest (encrypt failed, legacy row, or pre-KMS).
     * Do not send to KMS Decrypt — large payloads trigger AWS 6144-byte limit errors.
     */
    private function isPlainSerializedConfiguration($data): bool
    {
        if (!is_string($data) || $data === '') {
            return false;
        }

        if ($data === 'b:0;') {
            return true;
        }

        return (bool) preg_match('/^(a|s|i|d|b|O|N)[;:]/', $data);
    }
}
