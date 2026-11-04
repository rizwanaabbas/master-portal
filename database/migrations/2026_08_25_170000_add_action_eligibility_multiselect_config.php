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
        $configRecord = ModuleConfiguration::where('module_id', $module->id)->first();
        $existing = $configRecord && $configRecord->configurations
            ? (unserialize($configRecord->configurations) ?: [])
            : [];

        $keys = [
            'boost_eligible_primary_statuses',
            'drop_eligible_primary_statuses',
            'resubmit_eligible_primary_statuses',
        ];

        $updated = false;
        foreach ($keys as $key) {
            if (!isset($definitions[$key])) {
                continue;
            }

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
            $updated = true;
        }

        if (!$updated) {
            return;
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
        // Schema preserved; field metadata rollback is optional.
    }
};
