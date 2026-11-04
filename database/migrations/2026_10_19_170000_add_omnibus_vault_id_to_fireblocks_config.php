<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Modules\Fireblocks\Support\FireblocksModuleConfigDefinitions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $module = Module::where('name', 'Fireblocks')->first();
        if (!$module) {
            return;
        }

        $definition = FireblocksModuleConfigDefinitions::all()['omnibus_vault_id'];
        $configRecord = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($configRecord) {
            $existing = unserialize($configRecord->configurations) ?: [];
            if (!array_key_exists('omnibus_vault_id', $existing)) {
                $existing['omnibus_vault_id'] = $definition;
                $configRecord->configurations = serialize($existing);
                $configRecord->save();
            }
        } else {
            $configRecord = new ModuleConfiguration();
            $configRecord->module_id = $module->id;
            $configRecord->configurations = serialize(['omnibus_vault_id' => $definition]);
            $configRecord->save();
        }
    }

    public function down(): void
    {
        $module = Module::where('name', 'Fireblocks')->first();
        if (!$module) {
            return;
        }

        $configRecord = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$configRecord) {
            return;
        }

        $existing = unserialize($configRecord->configurations) ?: [];
        if (array_key_exists('omnibus_vault_id', $existing)) {
            unset($existing['omnibus_vault_id']);
            $configRecord->configurations = serialize($existing);
            $configRecord->save();
        }
    }
};
