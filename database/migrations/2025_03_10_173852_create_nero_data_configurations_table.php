<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        // Check if module exists
        $module = DB::table('modules')->where('name', 'Nero Data')->first();

        if (!$module) {
            // Create module if not exists
            $module_id = DB::table('modules')->insertGetId([
                'name' => 'NeroData',
                'is_enabled' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $module_id = $module->id;
        }

        // Configurations to insert/update
        $configurations = [
            "sandbox_api_base_url" => [
                "value" => "https://api.sandbox.nerodata.com/v1",
                "type" => "text",
                "label" => "Sandbox API Base URL"
            ],
            "sandbox_api_username" => [
                "value" => "xxxxxx",
                "type" => "text",
                "label" => "Sandbox API Username"
            ],
            "sandbox_api_password" => [
                "value" => "xxxxxxx",
                "type" => "text",
                "label" => "Sandbox API Password"
            ],
            "production_api_base_url" => [
                "value" => "https://api.nerodata.com/v1",
                "type" => "text",
                "label" => "Production API Base URL"
            ],
            "production_api_username" => [
                "value" => "xxxxxxx",
                "type" => "text",
                "label" => "Production API Username"
            ],
            "production_api_password" => [
                "value" => "xxxxxxx",
                "type" => "text",
                "label" => "Production API Password"
            ]
        ];

        // Check if module configuration exists
        $existingConfig = DB::table('module_configurations')->where('module_id', $module_id)->first();

        if ($existingConfig) {
            // Merge new configurations with existing ones
            $existingConfigurations = unserialize($existingConfig->configurations);
            $mergedConfigurations = array_merge($existingConfigurations, $configurations);
            
            // Update the configuration entry
            DB::table('module_configurations')->where('module_id', $module_id)->update([
                'configurations' => serialize($mergedConfigurations),
                'updated_at' => now()
            ]);
        } else {
            // Insert new configuration entry
            DB::table('module_configurations')->insert([
                'module_id' => $module_id,
                'configurations' => serialize($configurations),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    public function down()
    {
        // Remove module and configurations if needed
        $module = DB::table('modules')->where('name', 'Nero Data')->first();
        if ($module) {
            DB::table('module_configurations')->where('module_id', $module->id)->delete();
            DB::table('modules')->where('id', $module->id)->delete();
        }
    }
};
