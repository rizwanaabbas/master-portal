<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\ModuleConfiguration;

return new class extends Migration {
    public function up()
    {
        $module = Module::where('name', 'ChainAnalysis')->first();

        if (!$module) {
            $module = new Module();
            $module->name = 'ChainAnalysis';
            $module->is_enabled = 0;
            $module->save();
        }

        $configurations = [
            'api_key' => [
                'value' => 'xxxx-chainanalysis-api-key',
                'type' => 'text',
                'label' => 'API Key',
            ],
            'base_url' => [
                'value' => 'https://public.chainalysis.com/api/v1',
                'type' => 'text',
                'label' => 'Base URL',
            ],
           
            'risk_passing_criteria' => [
                'value' => 'Low',
                'type' => 'text',
                'label' => 'Risk Passing Criteria',
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
        $module = Module::where('name', 'ChainAnalysis')->first();

        if ($module) {
            ModuleConfiguration::where('module_id', $module->id)->delete();
            $module->delete();
        }
    }
};
