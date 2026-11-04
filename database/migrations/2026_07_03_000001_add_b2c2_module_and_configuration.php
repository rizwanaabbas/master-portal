<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\ModuleConfiguration;

return new class extends Migration {
    public function up()
    {
        $module = Module::where('name', 'B2c2')->first();

        if (!$module) {
            $module = new Module();
            $module->name = 'B2c2';
            $module->is_enabled = 0;
            $module->save();
        }

        $configurations = [
            'base_url' => [
                'value' => 'https://api.b2c2.com',
                'type' => 'text',
                'label' => 'Base URL',
            ],
            'api_token' => [
                'value' => '',
                'type' => 'text',
                'label' => 'API Token',
            ],
            'timeout' => [
                'value' => '30',
                'type' => 'text',
                'label' => 'Request Timeout (seconds)',
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
        $module = Module::where('name', 'B2c2')->first();

        if ($module) {
            ModuleConfiguration::where('module_id', $module->id)->delete();
            $module->delete();
        }
    }
};
