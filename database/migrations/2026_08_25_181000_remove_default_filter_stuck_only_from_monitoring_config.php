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
        if (!array_key_exists('default_filter_stuck_only', $existing)) {
            return;
        }

        unset($existing['default_filter_stuck_only']);
        $configRecord->configurations = serialize($existing);
        $configRecord->save();
    }

    public function down(): void
    {
        // Removed config key; no restore required.
    }
};
