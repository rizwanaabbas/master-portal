<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Modules\FireblocksVaultSweeping\Support\SweepModuleConfigDefinitions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $module = Module::where('name', 'FireblocksVaultSweeping')->first();

        if (!$module) {
            $module = new Module();
            $module->name = 'FireblocksVaultSweeping';
            $module->is_enabled = 0;
            $module->save();
        }

        $configurations = SweepModuleConfigDefinitions::all();
        $existingConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($existingConfig) {
            $existingConfigurations = unserialize($existingConfig->configurations);
            $existingConfig->configurations = serialize(
                SweepModuleConfigDefinitions::mergeInto(is_array($existingConfigurations) ? $existingConfigurations : [])
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
        $module = Module::where('name', 'FireblocksVaultSweeping')->first();

        if ($module) {
            ModuleConfiguration::where('module_id', $module->id)->delete();
            $module->delete();
        }
    }
};
