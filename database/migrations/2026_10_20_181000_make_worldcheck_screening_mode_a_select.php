<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $module = Module::where('name', 'WorldCheck')->first();
        if (!$module) {
            return;
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$record) {
            return;
        }

        $config = @unserialize($record->configurations);
        if (!is_array($config)) {
            return;
        }

        $current = $config['screening_mode']['value'] ?? 'INITIAL';
        $current = strtoupper((string) $current);
        if (!in_array($current, ['INITIAL', 'ONGOING'], true)) {
            $current = 'INITIAL';
        }

        $config['screening_mode'] = [
            'value' => $current,
            'type' => 'select',
            'label' => 'Screening mode',
            'options' => [
                'INITIAL' => 'INITIAL (one-time)',
                'ONGOING' => 'ONGOING (continuous monitoring)',
            ],
        ];

        $record->configurations = serialize($config);
        $record->save();
    }

    public function down(): void
    {
        $module = Module::where('name', 'WorldCheck')->first();
        if (!$module) {
            return;
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$record) {
            return;
        }

        $config = @unserialize($record->configurations);
        if (!is_array($config) || !isset($config['screening_mode'])) {
            return;
        }

        $config['screening_mode'] = [
            'value' => $config['screening_mode']['value'] ?? 'INITIAL',
            'type' => 'text',
            'label' => 'Screening mode: INITIAL (one-time) or ONGOING (continuous monitoring)',
        ];
        unset($config['screening_mode']['options']);

        $record->configurations = serialize($config);
        $record->save();
    }
};
