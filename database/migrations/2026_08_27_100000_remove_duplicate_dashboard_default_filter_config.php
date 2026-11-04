<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\ModuleConfiguration;

return new class extends Migration {
    public function up(): void
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
        $changed = false;

        foreach ([
            'default_filter_fb_status',
            'default_filter_fb_sub_status',
            'default_filter_direction',
            'default_filter_stuck_only',
        ] as $key) {
            if (array_key_exists($key, $existing)) {
                unset($existing[$key]);
                $changed = true;
            }
        }

        if (!$changed) {
            return;
        }

        $configRecord->configurations = serialize($existing);
        $configRecord->save();
    }

    public function down(): void
    {
        // Removed duplicate keys; no restore required.
    }
};
