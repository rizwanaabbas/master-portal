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

        $newKeys = [
            'boost_eligible_primary_statuses',
            'drop_eligible_primary_statuses',
            'default_filter_monitoring_state',
            'default_filter_fb_status',
            'default_filter_fb_sub_status',
            'default_filter_direction',
            'default_filter_stuck_only',
        ];

        $definitions = MonitoringModuleConfigDefinitions::all();
        $configRecord = ModuleConfiguration::where('module_id', $module->id)->first();
        $existing = $configRecord && $configRecord->configurations
            ? (unserialize($configRecord->configurations) ?: [])
            : [];

        $updated = false;
        foreach ($newKeys as $key) {
            if (!array_key_exists($key, $existing) && isset($definitions[$key])) {
                $existing[$key] = $definitions[$key];
                $updated = true;
            }
        }

        if ($updated) {
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
    }

    public function down(): void
    {
        $module = Module::where('name', 'FireblocksTransactionsMonitoring')->first();
        if (!$module) {
            return;
        }

        $configRecord = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$configRecord) {
            return;
        }

        $existing = unserialize($configRecord->configurations) ?: [];
        foreach ([
            'boost_eligible_primary_statuses',
            'drop_eligible_primary_statuses',
            'default_filter_monitoring_state',
            'default_filter_fb_status',
            'default_filter_fb_sub_status',
            'default_filter_direction',
            'default_filter_stuck_only',
        ] as $key) {
            unset($existing[$key]);
        }

        $configRecord->configurations = serialize($existing);
        $configRecord->save();
    }
};
