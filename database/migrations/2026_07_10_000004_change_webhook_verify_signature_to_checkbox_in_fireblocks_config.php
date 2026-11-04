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

        $record = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$record) {
            return;
        }

        $config = unserialize($record->configurations);

        if (!is_array($config) || !isset($config['webhook_verify_signature'])) {
            return;
        }

        $currentValue = $config['webhook_verify_signature']['value'] ?? '1';
        $normalizedValue = in_array($currentValue, [1, '1', true, 'true'], true) ? '1' : '0';

        $config['webhook_verify_signature'] = [
            'value' => $normalizedValue,
            'type'  => 'checkbox',
            'label' => $config['webhook_verify_signature']['label'] ?? 'Webhook Verify Signature',
        ];

        $record->configurations = serialize($config);
        $record->save();
    }

    public function down()
    {
        $module = Module::where('name', 'Fireblocks')->first();

        if (!$module) {
            return;
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$record) {
            return;
        }

        $config = unserialize($record->configurations);

        if (!is_array($config) || !isset($config['webhook_verify_signature'])) {
            return;
        }

        $config['webhook_verify_signature'] = [
            'value' => $config['webhook_verify_signature']['value'] ?? '1',
            'type'  => 'text',
            'label' => $config['webhook_verify_signature']['label'] ?? 'Webhook Verify Signature',
        ];

        $record->configurations = serialize($config);
        $record->save();
    }
};
