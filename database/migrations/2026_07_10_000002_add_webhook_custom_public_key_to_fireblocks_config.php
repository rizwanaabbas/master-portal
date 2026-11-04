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
            'webhook_custom_public_key' => [
                'value' => '',
                'type'  => 'textarea',
                'label' => 'Webhook V2 Public Key (PEM) — paste from Fireblocks Console',
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
                unset($existing['webhook_custom_public_key']);
                $record->configurations = serialize($existing);
                $record->save();
            }
        }
    }
};
