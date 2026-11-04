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

        $merged = MonitoringModuleConfigDefinitions::mergeInto($existing);
        if (isset($merged['dashboard_page_size'])) {
            $current = (string) ($merged['dashboard_page_size']['value'] ?? '');
            if ($current === '' || $current === '25') {
                $merged['dashboard_page_size']['value'] = '15';
            }
        }

        $record->configurations = serialize($merged);
        $record->save();
    }

    public function down(): void
    {
        // Metadata + default page size refresh — no rollback.
    }
};
