<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Modules\FireblocksTransactionsMonitoring\Support\MonitoringModuleConfigDefinitions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $module = Module::where('name', 'FireblocksTransactionsMonitoring')->first();
        if (!$module) {
            return;
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$record) {
            return;
        }

        $existing = unserialize($record->configurations);
        if (!is_array($existing)) {
            return;
        }

        $record->configurations = serialize(MonitoringModuleConfigDefinitions::mergeInto($existing));
        $record->save();
    }

    public function down(): void
    {
        // Keys are harmless if left in serialized config.
    }
};
