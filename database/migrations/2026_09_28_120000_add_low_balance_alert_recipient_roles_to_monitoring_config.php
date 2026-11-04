<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Modules\FireblocksTransactionsMonitoring\Support\MonitoringModuleConfigDefinitions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $module = Module::where('name', 'FireblocksTransactionsMonitoring')->first();
        if (!$module) {
            return;
        }

        $definitions = MonitoringModuleConfigDefinitions::all();
        if (!isset($definitions['low_balance_alert_recipient_roles'])) {
            return;
        }

        $configRecord = ModuleConfiguration::where('module_id', $module->id)->first();
        $existing = $configRecord && $configRecord->configurations
            ? (unserialize($configRecord->configurations) ?: [])
            : [];

        $key = 'low_balance_alert_recipient_roles';
        $definition = $definitions[$key];

        if (isset($existing[$key])) {
            $existing[$key] = array_merge($definition, [
                'value' => trim((string) ($existing[$key]['value'] ?? '')) !== ''
                    ? $existing[$key]['value']
                    : $definition['value'],
            ]);
        } else {
            $existing[$key] = $definition;
        }

        if ($configRecord) {
            $configRecord->configurations = serialize($existing);
            $configRecord->save();
        } else {
            $configRecord = new ModuleConfiguration();
            $configRecord->module_id = $module->id;
            $configRecord->configurations = serialize($existing);
            $configRecord->save();
        }
    }

    public function down(): void
    {
        // Field metadata preserved on rollback.
    }
};
