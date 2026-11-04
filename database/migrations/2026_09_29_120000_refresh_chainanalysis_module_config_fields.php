<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Modules\Chainalysis\Support\ChainAnalysisModuleConfigDefinitions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $module = Module::where('name', 'ChainAnalysis')->first();

        if (!$module) {
            return;
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$record) {
            $config = new ModuleConfiguration();
            $config->module_id = $module->id;
            $config->configurations = serialize(ChainAnalysisModuleConfigDefinitions::mergeInto([]));
            $config->save();

            return;
        }

        $existing = unserialize($record->configurations);
        $merged = ChainAnalysisModuleConfigDefinitions::mergeInto(is_array($existing) ? $existing : []);
        $record->configurations = serialize($merged);
        $record->save();
    }

    public function down(): void
    {
        // Non-destructive: leave merged definitions in place.
    }
};
