<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\ModuleConfiguration;

return new class extends Migration {
    public function up()
    {
        $module = Module::where('name', 'Security')->first();

        if (!$module) {
            $module = new Module();
            $module->name = 'Security';
            $module->is_enabled = 0;
            $module->save();
        }

        $configurations = [
            'restrict_cors' => [
                'value' => '0',
                'type' => 'select',
                'label' => 'CORS policy',
                'options' => [
                    '0' => 'Allow all origins (* — current)',
                    '1' => 'Only allow listed origins',
                ],
                'hint' => 'Allow all keeps Access-Control-Allow-Origin: *. Native mobile apps do not use CORS. Browser and WebView clients do. Switch to listed origins only after you have added every real origin below. If a web client cannot call the API, set this back to Allow all or add the missing origin.',
                'sample' => '0 = * (current). 1 = use Allowed origins.',
            ],
            'allowed_origins' => [
                'value' => 'https://portal.example.com,https://app.example.com',
                'type' => 'textarea',
                'label' => 'Allowed origins (comma-separated)',
                'show_when' => [
                    'field' => 'restrict_cors',
                    'value' => '1',
                ],
                'hint' => 'Exact origins only: scheme + host + port. No paths. Separate with commas. Do not include a trailing slash. Use * only if you must allow every origin (same as today). After saving, test login and API calls from each origin. If a client is blocked, add its origin here or turn Restrict CORS off.',
                'sample' => 'https://cib-portal.example.com,https://www.cib-portal.example.com,https://mobile-web.example.com,http://localhost:3000',
            ],
            'hide_exception_details' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Hide exception details from clients',
                'hint' => 'When off, JSON error fields may include SQLSTATE, file paths, or PHP messages (useful while testing). When on, those internal strings are replaced with a generic message. User-facing texts such as Invalid Account ID stay as they are. If the mobile app depends on a specific exception string, turn this off.',
                'sample' => '0 = show raw errors (current). 1 = clients see: Something went wrong. Please try again.',
            ],
            'encrypt_session' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Encrypt session payloads in storage',
                'hint' => 'When off, session rows/files are readable if the database is dumped. When on, Laravel encrypts the session blob. Enabling this logs everyone out once (old sessions cannot be decrypted). Impersonate from portal → mobile API also reads session payloads — test impersonate after turning this on. If staff cannot stay logged in, turn it off.',
                'sample' => '0 = unencrypted sessions (current). 1 = encrypt (expect a one-time logout).',
            ],
            'ban_svg_uploads' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Ban SVG file uploads',
                'hint' => 'SVG can contain script and run in the portal origin (stored XSS). When off, SVG logos and KYC files still upload. When on, .svg / image/svg+xml are rejected. If a currency or company logo upload fails, turn this off or convert logos to PNG.',
                'sample' => '0 = allow svg (current). 1 = reject files named *.svg',
            ],
            'require_bitpace_hmac' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Require Bitpace webhook HMAC (even with no signature header)',
                'hint' => 'When off, Bitpace callbacks still work if there is no signature header (order is re-checked via Bitpace getOrderV2). HMAC is only required when Bitpace already sends X-Signature, X-Bitpace-Signature, or X-Callback-Signature. When on, a missing or wrong HMAC is rejected (401) — deposits will stop until Bitpace signs the raw body with HMAC-SHA256 using the module callback secret. Test one sandbox deposit before leaving this on.',
                'sample' => 'Header: X-Signature: <hmac-sha256 of raw JSON body using Bitpace callback_secret>. 0 = optional HMAC (current). 1 = HMAC required.',
            ],
        ];

        $existingConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($existingConfig) {
            $existingConfigurations = unserialize($existingConfig->configurations);
            $newKeys = array_diff_key($configurations, is_array($existingConfigurations) ? $existingConfigurations : []);
            if (!empty($newKeys)) {
                $existingConfig->configurations = serialize(array_merge($existingConfigurations, $newKeys));
                $existingConfig->save();
            }
        } else {
            $config = new ModuleConfiguration();
            $config->module_id = $module->id;
            $config->configurations = serialize($configurations);
            $config->save();
        }
    }

    public function down()
    {
        $module = Module::where('name', 'Security')->first();

        if ($module) {
            ModuleConfiguration::where('module_id', $module->id)->delete();
            $module->delete();
        }
    }
};
