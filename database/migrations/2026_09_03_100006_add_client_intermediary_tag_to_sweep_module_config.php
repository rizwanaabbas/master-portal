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
            return;
        }

        $record = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$record) {
            return;
        }

        $existing = unserialize($record->configurations);

        if (!is_array($existing)) {
            $existing = [];
        }

        $record->configurations = serialize(
            SweepModuleConfigDefinitions::mergeInto($existing)
        );
        $record->save();
    }

    public function down(): void
    {
        // Config key merge is non-destructive; no down migration required.
    }
};
