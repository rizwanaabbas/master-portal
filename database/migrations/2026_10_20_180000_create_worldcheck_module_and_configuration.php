<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $module = Module::where('name', 'WorldCheck')->first();
        if (!$module) {
            $module = new Module();
            $module->name = 'WorldCheck';
            $module->is_enabled = 1;
            $module->save();
        } else {
            $module->is_enabled = 1;
            $module->save();
        }

        $configurations = [
            'worldcheck_enabled' => [
                'value' => '1',
                'type' => 'checkbox',
                'label' => 'Enable World-Check API calls (Request / Get Report / New Screening)',
            ],
            'max_screens_per_day' => [
                'value' => '50',
                'type' => 'number',
                'label' => 'Max screening requests per day (0 = unlimited)',
            ],
            'report_status_max_retries' => [
                'value' => '2',
                'type' => 'number',
                'label' => 'Max retries when fetching report status (0 = no retry)',
            ],
            'screening_mode' => [
                'value' => 'INITIAL',
                'type' => 'select',
                'label' => 'Screening mode',
                'options' => [
                    'INITIAL' => 'INITIAL (one-time)',
                    'ONGOING' => 'ONGOING (continuous monitoring)',
                ],
            ],
        ];

        $existingConfig = ModuleConfiguration::where('module_id', $module->id)->first();
        if ($existingConfig) {
            $existing = @unserialize($existingConfig->configurations);
            if (!is_array($existing)) {
                $existing = [];
            }
            foreach ($configurations as $key => $definition) {
                if (!array_key_exists($key, $existing)) {
                    $existing[$key] = $definition;
                } else {
                    $existing[$key]['type'] = $definition['type'];
                    $existing[$key]['label'] = $definition['label'];
                    if (isset($definition['options'])) {
                        $existing[$key]['options'] = $definition['options'];
                    }
                }
            }
            $existingConfig->configurations = serialize($existing);
            $existingConfig->save();
        } else {
            $config = new ModuleConfiguration();
            $config->module_id = $module->id;
            $config->configurations = serialize($configurations);
            $config->save();
        }
    }

    public function down(): void
    {
        $module = Module::where('name', 'WorldCheck')->first();
        if (!$module) {
            return;
        }

        ModuleConfiguration::where('module_id', $module->id)->delete();
        $module->delete();
    }
};
