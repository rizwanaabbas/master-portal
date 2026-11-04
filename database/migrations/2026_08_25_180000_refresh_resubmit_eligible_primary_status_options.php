<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Modules\FireblocksTransactionsMonitoring\Support\MonitoringModuleConfigDefinitions;

return new class extends Migration {
    public function up(): void
    {
        $module = Module::where('name', 'FireblocksTransactionsMonitoring')->first();
        if (!$module) {
            return;
        }

        $definitions = MonitoringModuleConfigDefinitions::all();
        $key = 'resubmit_eligible_primary_statuses';
        if (!isset($definitions[$key])) {
            return;
        }

        $configRecord = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$configRecord) {
            return;
        }

        $existing = unserialize($configRecord->configurations) ?: [];
        if (!isset($existing[$key])) {
            return;
        }

        $currentValue = trim((string) ($existing[$key]['value'] ?? ''));

        $existing[$key] = array_merge($definitions[$key], [
            'value' => $currentValue !== ''
                ? $currentValue
                : $definitions[$key]['value'],
        ]);

        $configRecord->configurations = serialize($existing);
        $configRecord->save();
    }

    public function down(): void
    {
        // Metadata refresh only; preserve operator selections.
    }
};
