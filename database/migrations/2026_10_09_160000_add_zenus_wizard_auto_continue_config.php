<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $module = Module::where('name', 'Zenus')->first();
        if (!$module) {
            return;
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$record) {
            return;
        }

        $existing = unserialize($record->configurations);
        if (!is_array($existing) || isset($existing['wizard_auto_continue'])) {
            return;
        }

        $existing['wizard_auto_continue'] = [
            'value' => '0',
            'type' => 'select',
            'label' => 'Account wizard mode',
            'hint' => 'Auto continue runs each Zenus wizard step until an error. User guided waits for Next.',
            'options' => [
                '0' => 'User guided',
                '1' => 'Auto continue',
            ],
        ];

        $record->configurations = serialize($existing);
        $record->save();
    }

    public function down(): void
    {
        $module = Module::where('name', 'Zenus')->first();
        if (!$module) {
            return;
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$record) {
            return;
        }

        $existing = unserialize($record->configurations);
        if (!is_array($existing) || !isset($existing['wizard_auto_continue'])) {
            return;
        }

        unset($existing['wizard_auto_continue']);
        $record->configurations = serialize($existing);
        $record->save();
    }
};
