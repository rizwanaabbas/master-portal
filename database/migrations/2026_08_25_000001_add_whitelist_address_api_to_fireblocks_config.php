<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\ModuleConfiguration;

return new class extends Migration {
    public function up()
    {
        $module = Module::where('name', 'Fireblocks')->first();

        if (!$module) {
            return;
        }

        $newConfig = [
            'whitelist_address_api' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Whitelist Address API Key',
            ],
            'whitelist_address_api_secret_path' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Whitelist Address API Secret Path',
            ],
        ];

        $configRecord = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($configRecord) {
            $existingConfigurations = unserialize($configRecord->configurations) ?: [];

            $updated = false;
            foreach ($newConfig as $key => $configuration) {
                if (!array_key_exists($key, $existingConfigurations)) {
                    $existingConfigurations[$key] = $configuration;
                    $updated = true;
                }
            }

            if ($updated) {
                $configRecord->configurations = serialize($existingConfigurations);
                $configRecord->save();
            }
        } else {
            $configRecord = new ModuleConfiguration();
            $configRecord->module_id = $module->id;
            $configRecord->configurations = serialize($newConfig);
            $configRecord->save();
        }
    }

    public function down()
    {
        $module = Module::where('name', 'Fireblocks')->first();

        if (!$module) {
            return;
        }

        $configRecord = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($configRecord) {
            $existingConfigurations = unserialize($configRecord->configurations) ?: [];

            $updated = false;
            foreach (['whitelist_address_api', 'whitelist_address_api_secret_path'] as $key) {
                if (array_key_exists($key, $existingConfigurations)) {
                    unset($existingConfigurations[$key]);
                    $updated = true;
                }
            }

            if ($updated) {
                $configRecord->configurations = serialize($existingConfigurations);
                $configRecord->save();
            }
        }
    }
};
