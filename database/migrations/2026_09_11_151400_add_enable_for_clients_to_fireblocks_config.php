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
            'enable_for_clients' => [
                'value' => 'all',
                'type' => 'select',
                'label' => 'Enable Fireblocks for clients',
                'options' => [
                    'all' => 'All',
                    'specific' => 'Specific client IDs',
                ],
            ],
            'enable_for_client_ids' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Client IDs',
                'placeholder' => 'e.g. 12, 34, 56',
                'required' => false,
                'show_when' => [
                    'field' => 'enable_for_clients',
                    'value' => 'specific',
                ],
            ],
        ];

        $record = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($record) {
            $existing = unserialize($record->configurations);
            $newKeys = array_diff_key($newConfig, is_array($existing) ? $existing : []);
            if (!empty($newKeys)) {
                $record->configurations = serialize(array_merge(is_array($existing) ? $existing : [], $newKeys));
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
                unset($existing['enable_for_clients'], $existing['enable_for_client_ids']);
                $record->configurations = serialize($existing);
                $record->save();
            }
        }
    }
};
