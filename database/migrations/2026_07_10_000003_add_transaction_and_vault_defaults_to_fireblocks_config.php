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
            'default_fee_level' => [
                'value' => 'MEDIUM',
                'type'  => 'select',
                'label' => 'Default Fee Level',
                'options' => [
                    'LOW'    => 'Low',
                    'MEDIUM' => 'Medium',
                    'HIGH'   => 'High',
                ],
            ],
            'vault_hidden_on_ui' => [
                'value' => '0',
                'type'  => 'checkbox',
                'label' => 'Hide New Vault Accounts on Fireblocks UI (hiddenOnUI)',
            ],
            'vault_auto_fuel' => [
                'value' => '1',
                'type'  => 'checkbox',
                'label' => 'Auto Fuel New Vault Accounts (autoFuel)',
            ],
            'show_vaults_menu' => [
                'value' => '0',
                'type'  => 'checkbox',
                'label' => 'Show Fireblocks Vaults Menu Item',
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
                unset(
                    $existing['default_fee_level'],
                    $existing['vault_hidden_on_ui'],
                    $existing['vault_auto_fuel'],
                    $existing['show_vaults_menu']
                );
                $record->configurations = serialize($existing);
                $record->save();
            }
        }
    }
};
