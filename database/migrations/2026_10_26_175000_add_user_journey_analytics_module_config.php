<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use Illuminate\Database\Migrations\Migration;

class AddUserJourneyAnalyticsModuleConfig extends Migration
{
    public function up(): void
    {
        $module = Module::where('name', 'UserJourneyAnalytics')->first();
        if (!$module) {
            $module = new Module();
            $module->name = 'UserJourneyAnalytics';
            $module->is_enabled = 1;
            $module->save();
        }

        $fields = [
            'ip2location_on_event' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Look up location on every event',
                'hint' => 'When off (default), IP2Location / IP geo is only called when you open a visit detail. When on, each new journey event looks up country from the IP (uses more API calls).',
                'sample' => '0 = detail page only. 1 = every new event.',
            ],
            'ip2location_api_key' => [
                'value' => '',
                'type' => 'text',
                'label' => 'IP2Location API key',
                'hint' => 'Optional. If set, visit/event lookups use api.ip2location.io. If empty, ip-api.com is used as a fallback.',
                'sample' => 'From https://www.ip2location.io/',
            ],
        ];

        $existing = ModuleConfiguration::where('module_id', $module->id)->first();
        if ($existing) {
            $current = unserialize($existing->configurations);
            $current = is_array($current) ? $current : [];
            $existing->configurations = serialize(array_merge($fields, $current));
            $existing->save();
        } else {
            $config = new ModuleConfiguration();
            $config->module_id = $module->id;
            $config->configurations = serialize($fields);
            $config->save();
        }
    }

    public function down(): void
    {
        $module = Module::where('name', 'UserJourneyAnalytics')->first();
        if (!$module) {
            return;
        }
        $existing = ModuleConfiguration::where('module_id', $module->id)->first();
        if (!$existing) {
            return;
        }
        $current = unserialize($existing->configurations);
        if (!is_array($current)) {
            return;
        }
        unset($current['ip2location_on_event'], $current['ip2location_api_key']);
        $existing->configurations = serialize($current);
        $existing->save();
    }
}
