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
        $record = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($record) {
            $existing = unserialize($record->configurations);
            $record->configurations = serialize(
                MonitoringModuleConfigDefinitions::mergeInto(is_array($existing) ? $existing : [])
            );
            $record->save();
        } else {
            $config = new ModuleConfiguration();
            $config->module_id = $module->id;
            $config->configurations = serialize($definitions);
            $config->save();
        }
    }

    public function down(): void
    {
        // Non-destructive metadata refresh — no rollback required.
    }
};
