<?php

namespace App\Console\Commands;

use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Helpers\KMSHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReencryptModuleConfiguration extends Command
{
    protected $signature = 'module-config:reencrypt
                            {module? : Module name (e.g. FireblocksTransactionsMonitoring). Omit for all Fireblocks* modules}
                            {--dry-run : Report only, do not write}';

    protected $description = 'Re-save module configuration rows so large payloads use KMS envelope encryption';

    public function handle(): int
    {
        $nameFilter = $this->argument('module');
        $dryRun = (bool) $this->option('dry-run');

        $query = Module::query();
        if ($nameFilter) {
            $query->where('name', $nameFilter);
        } else {
            $query->where('name', 'like', 'Fireblocks%');
        }

        $modules = $query->get();
        if ($modules->isEmpty()) {
            $this->error('No matching modules.');

            return self::FAILURE;
        }

        foreach ($modules as $module) {
            $record = ModuleConfiguration::where('module_id', $module->id)->first();
            if (!$record) {
                $this->warn("No configuration row for {$module->name}");
                continue;
            }

            $raw = DB::table('module_configurations')
                ->where('id', $record->id)
                ->value('configurations');

            if (!is_string($raw) || $raw === '') {
                $this->warn("Empty configurations for {$module->name}");
                continue;
            }

            $rawLen = strlen($raw);
            $format = str_starts_with($raw, KMSHelper::ENVELOPE_PREFIX)
                ? 'envelope'
                : (preg_match('/^(a|s|i|d|b|O|N)[;:]/', $raw) ? 'plain-serialized' : 'kms-direct-or-unknown');

            $this->line("{$module->name}: raw_len={$rawLen} format={$format}");

            if ($dryRun) {
                continue;
            }

            $plain = $record->configurations;
            if (!is_string($plain) || $plain === '') {
                $this->error("Could not read/decrypt configuration for {$module->name}");
                continue;
            }

            $record->configurations = $plain;
            $record->save();

            $newRaw = DB::table('module_configurations')
                ->where('id', $record->id)
                ->value('configurations');

            $newFormat = is_string($newRaw) && str_starts_with($newRaw, KMSHelper::ENVELOPE_PREFIX)
                ? 'envelope'
                : (is_string($newRaw) && preg_match('/^(a|s|i|d|b|O|N)[;:]/', $newRaw) ? 'plain-serialized' : 'kms-direct-or-unknown');

            $this->info("  → saved format={$newFormat} len=" . strlen((string) $newRaw));
        }

        if ($dryRun) {
            $this->comment('Dry run — no changes written.');
        }

        return self::SUCCESS;
    }
}
