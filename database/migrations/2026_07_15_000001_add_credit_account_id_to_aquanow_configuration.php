<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\ModuleConfiguration;

return new class extends Migration {
    public function up()
    {
        $module = Module::where('name', 'Aquanow')->first();

        if (!$module) {
            return;
        }

        $newField = [
            'credit_account_id' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Credit Account Id',
            ],
        ];

        $existingConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($existingConfig) {
            $existingConfigurations = unserialize($existingConfig->configurations);
            $newKeys = array_diff_key($newField, $existingConfigurations);
            if (!empty($newKeys)) {
                $existingConfig->configurations = serialize(array_merge($existingConfigurations, $newKeys));
                $existingConfig->save();
            }
        } else {
            $config = new ModuleConfiguration();
            $config->module_id = $module->id;
            $config->configurations = serialize($newField);
            $config->save();
        }
    }

    public function down()
    {
        $module = Module::where('name', 'Aquanow')->first();

        if (!$module) {
            return;
        }

        $existingConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($existingConfig) {
            $existingConfigurations = unserialize($existingConfig->configurations);
            unset($existingConfigurations['credit_account_id']);
            $existingConfig->configurations = serialize($existingConfigurations);
            $existingConfig->save();
        }
    }
};
