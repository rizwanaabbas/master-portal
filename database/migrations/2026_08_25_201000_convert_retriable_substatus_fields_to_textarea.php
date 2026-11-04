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
        $keys = [
            'retriable_substatuses',
            'non_retriable_substatuses',
        ];

        $configRecord = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$configRecord) {
            return;
        }

        $existing = unserialize($configRecord->configurations) ?: [];
        $updated = false;

        foreach ($keys as $key) {
            if (!isset($definitions[$key], $existing[$key])) {
                continue;
            }

            $currentValue = $existing[$key]['value'] ?? '';
            if (is_array($currentValue)) {
                $currentValue = implode(',', array_filter(array_map('trim', $currentValue)));
            }

            $existing[$key] = array_merge($definitions[$key], [
                'type' => 'textarea',
                'value' => trim((string) $currentValue) !== ''
                    ? trim((string) $currentValue)
                    : $definitions[$key]['value'],
            ]);
            unset($existing[$key]['options'], $existing[$key]['option_groups']);
            $updated = true;
        }

        if ($updated) {
            $configRecord->configurations = serialize($existing);
            $configRecord->save();
        }
    }

    public function down(): void
    {
        // Field type change only.
    }
};
