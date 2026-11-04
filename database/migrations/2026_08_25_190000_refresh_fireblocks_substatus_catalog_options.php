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
        $keysToRefresh = [
            'monitored_fireblocks_statuses',
            'retriable_substatuses',
            'non_retriable_substatuses',
            'resubmit_eligible_primary_statuses',
            'boost_eligible_primary_statuses',
            'drop_eligible_primary_statuses',
        ];

        $configRecord = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$configRecord) {
            return;
        }

        $existing = unserialize($configRecord->configurations) ?: [];
        $updated = false;

        foreach ($keysToRefresh as $key) {
            if (!isset($definitions[$key], $existing[$key])) {
                continue;
            }

            $patch = [];

            if (array_key_exists('options', $definitions[$key])) {
                $patch['options'] = $definitions[$key]['options'];
            }

            if (!empty($definitions[$key]['option_groups'])) {
                $patch['option_groups'] = $definitions[$key]['option_groups'];
            }

            // Fields converted to textarea (or removed from schema) have no options — skip safely.
            if ($patch === []) {
                continue;
            }

            $existing[$key] = array_merge($existing[$key], $patch);

            if (in_array($key, ['retriable_substatuses', 'non_retriable_substatuses'], true)
                && empty(trim((string) ($existing[$key]['value'] ?? '')))) {
                $existing[$key]['value'] = $definitions[$key]['value'];
            }
            $updated = true;
        }

        if ($updated) {
            $configRecord->configurations = serialize($existing);
            $configRecord->save();
        }
    }

    public function down(): void
    {
        // Options refresh only — no rollback of user-selected values.
    }
};
