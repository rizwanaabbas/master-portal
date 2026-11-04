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
            $module = new Module();
            $module->name = 'FireblocksTransactionsMonitoring';
            $module->is_enabled = 0;
            $module->save();
        }

        $configurations = MonitoringModuleConfigDefinitions::all();

        $existingConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($existingConfig) {
            $existingConfigurations = unserialize($existingConfig->configurations);
            $existingConfig->configurations = serialize(
                MonitoringModuleConfigDefinitions::mergeInto(is_array($existingConfigurations) ? $existingConfigurations : [])
            );
            $existingConfig->save();
        } else {
            $config = new ModuleConfiguration();
            $config->module_id = $module->id;
            $config->configurations = serialize($configurations);
            $config->save();
        }
    }

    public function down(): void
    {
        $module = Module::where('name', 'FireblocksTransactionsMonitoring')->first();

        if ($module) {
            ModuleConfiguration::where('module_id', $module->id)->delete();
            $module->delete();
        }
    }
};
