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
            'travel_rules_enabled' => [
                'value' => '0',
                'type'  => 'checkbox',
                'label' => 'Travel Rules Enabled (for Beneficiaries)',
            ],
        ];

        $record = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($record) {
            $existing = unserialize($record->configurations);
            $newKeys = array_diff_key($newConfig, is_array($existing) ? $existing : []);
            if (!empty($newKeys)) {
                $record->configurations = serialize(array_merge($existing, $newKeys));
                $record->save();
            }
        } else {
            $config = new ModuleConfiguration();
            $config->module_id = $module->id;
            $config->configurations = serialize($newConfig);
            $config->save();
        }
    }

    public function down()
    {
        $module = Module::where('name', 'Fireblocks')->first();

        if (!$module) {
            return;
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($record) {
            $existing = unserialize($record->configurations);
            if (is_array($existing)) {
                unset($existing['travel_rules_enabled']);
                $record->configurations = serialize($existing);
                $record->save();
            }
        }
    }
};
