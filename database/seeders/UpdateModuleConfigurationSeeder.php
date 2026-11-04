<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleConfiguration;
use Illuminate\Database\Seeder;

class UpdateModuleConfigurationSeeder extends Seeder
{
    public function run()
    {
        $moduleNames = ['MatchMove', 'E-Money'];

        $newConfig = [
            'has_physical_card' => [
                'value' => false,
                'type' => 'checkbox',
                'label' => 'Has Physical Card',
                'disabled' => true,
            ]
        ];

        foreach ($moduleNames as $name) {
            $module = Module::where('name', $name)->first();

            if (!$module) {
                $this->command->info("Module {$name} not found.");
                continue;
            }

            $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();

            if (!$moduleConfig) {
                $this->command->info("ModuleConfiguration for module {$name} not found.");
                continue;
            }

            $configs = $moduleConfig->configurations ?? [];

            if (is_string($configs)) {
                $configs = @unserialize($configs) ?: [];
            }

            // Add new config if not exists
            if (!array_key_exists('has_physical_card', $configs)) {
                $configs['has_physical_card'] = $newConfig['has_physical_card'];
            }

            $moduleConfig->configurations = serialize($configs);

            $moduleConfig->save();

            $this->command->info("Updated ModuleConfiguration for module {$name}.");
        }
    }
}
