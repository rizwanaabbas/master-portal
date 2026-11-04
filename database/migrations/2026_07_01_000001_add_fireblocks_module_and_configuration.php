<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\ModuleConfiguration;

return new class extends Migration {
    public function up()
    {
        $module = Module::where('name', 'Fireblocks')->first();

        if (!$module) {
            $module = new Module();
            $module->name = 'Fireblocks';
            $module->is_enabled = 0;
            $module->save();
        }

        $configurations = [
            
            'base_url' => [
                'value' => 'https://api.fireblocks.io',
                'type' => 'text',
                'label' => 'Base URL',
            ],
            'api_key' => [
                'value' => 'xxxx-production-api-key',
                'type' => 'text',
                'label' => 'API Key',
            ],
            'fire_blocks_secret_key_path' => [
                'value' => '/var/www/etc',
                'type' => 'text',
                'label' => 'Fire Blocks Secret Key Path',
            ],
           
            'source_vault_account_id' => [
                'value' => 'xxxx',
                'type' => 'text',
                'label' => 'Source Vault Account ID',
            ],
            'webhook_verify_signature' => [
                'value' => '1',
                'type' => 'text',
                'label' => 'Webhook Verify Signature',
            ],
            'webhook_public_key_type' => [
                'value' => 'sandbox',
                'type' => 'text',
                'label' => 'Webhook Public Key Type (sandbox/production/eu)',
            ],
        ];

        $existingConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($existingConfig) {
            $existingConfigurations = unserialize($existingConfig->configurations);
            $newKeys = array_diff_key($configurations, $existingConfigurations);
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
        $module = Module::where('name', 'Fireblocks')->first();

        if ($module) {
            ModuleConfiguration::where('module_id', $module->id)->delete();
            $module->delete();
        }
    }
};
