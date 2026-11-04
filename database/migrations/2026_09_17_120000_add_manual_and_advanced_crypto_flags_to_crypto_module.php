<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $module = Module::where('name', 'Crypto')->first();

        if (!$module) {
            $module = new Module();
            $module->name = 'Crypto';
            $module->is_enabled = 0;
            $module->save();
        }

        $bitpaceHintSuffix = ' When the Bitpace module is enabled, Bitpace overrides these settings — both options are treated as on and the checkboxes are locked here.';

        $configurations = [
            'enable_manual_crypto' => [
                'value' => '1',
                'type' => 'checkbox',
                'label' => 'Enable Manual Crypto',
                'hint' => 'Applies when Bitpace is disabled: allow manual crypto transaction flows (admin/client deposit, buy, sell, withdraw without the Bitpace API, and Bitpace Manual in admin).'
                    . $bitpaceHintSuffix,
                'sample' => '1 = manual flows available (when Bitpace is off)',
                'doc_url' => '',
            ],
            'enable_advanced_crypto' => [
                'value' => '1',
                'type' => 'checkbox',
                'label' => 'Enable Advanced Crypto',
                'hint' => 'Applies when Bitpace is disabled: allow advanced integrations (Fireblocks, liquidity providers, Nexus, Independent Reserve, etc.).'
                    . $bitpaceHintSuffix,
                'sample' => '1 = advanced methods available (when Bitpace is off)',
                'doc_url' => '',
            ],
        ];

        $existingConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($existingConfig) {
            $existingConfigurations = unserialize($existingConfig->configurations);
            if (!is_array($existingConfigurations)) {
                $existingConfigurations = [];
            }
            $newKeys = array_diff_key($configurations, $existingConfigurations);
            if (!empty($newKeys)) {
                $merged = array_merge($existingConfigurations, $newKeys);
                $existingConfig->configurations = serialize(
                    \App\Services\CryptoConfigService::applyBitpaceOverrideToConfigArray($merged)
                );
                $existingConfig->save();
            }
        } else {
            $config = new ModuleConfiguration();
            $config->module_id = $module->id;
            $config->configurations = serialize($configurations);
            $config->save();
        }
    }

    public function down(): void
    {
        $module = Module::where('name', 'Crypto')->first();

        if (!$module) {
            return;
        }

        $configRow = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$configRow || empty($configRow->configurations)) {
            return;
        }

        $configurations = @unserialize($configRow->configurations);

        if (!is_array($configurations)) {
            return;
        }

        foreach (['enable_manual_crypto', 'enable_advanced_crypto'] as $key) {
            unset($configurations[$key]);
        }

        $configRow->configurations = serialize($configurations);
        $configRow->save();
    }
};
