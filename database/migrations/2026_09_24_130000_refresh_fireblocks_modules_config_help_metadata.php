<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Modules\Fireblocks\Support\FireblocksModuleConfigDefinitions;
use App\Modules\FireblocksTransactionsMonitoring\Support\MonitoringModuleConfigDefinitions;
use App\Modules\FireblocksVaultSweeping\Support\SweepModuleConfigDefinitions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->refreshModule('Fireblocks', FireblocksModuleConfigDefinitions::class);
        $this->refreshModule('FireblocksTransactionsMonitoring', MonitoringModuleConfigDefinitions::class);
        $this->refreshModule('FireblocksVaultSweeping', SweepModuleConfigDefinitions::class);
    }

    /**
     * @param  class-string  $definitionsClass
     */
    protected function refreshModule(string $moduleName, string $definitionsClass): void
    {
        $module = Module::where('name', $moduleName)->first();
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

        $record->configurations = serialize($definitionsClass::mergeInto($existing));
        $record->save();
    }

    public function down(): void
    {
        // Metadata refresh — no rollback.
    }
};
