<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Models\ModuleConfiguration;
use Illuminate\Support\Facades\Schema;

class AddProductIdFieldToMatchmoveModuleConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if module exists
        $module = DB::table('modules')->where('name', 'MatchMove')->first();

        if (!$module) {
            // Create module if not exists
            $module_id = DB::table('modules')->insertGetId([
                'name' => 'MatchMove',
                'is_enabled' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $module_id = $module->id;
        }

        // MatchMove configuration data
        $configurations = [
            // Sandbox values
            "sandbox_product_id" => [
                "value" => "4",
                "type" => "text",
                "label" => "Sandbox Match-Move Product Id"
            ],

            // Production values
            "production_product_id" => [
                "value" => "xxxx",
                "type" => "text",
                "label" => "Match-Move Product Id"
            ],
        ];

        // Check if module configuration already exists
        $existingConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($existingConfig) {
            $existingConfigurations = unserialize($existingConfig->configurations);
            $mergedConfigurations = array_merge($existingConfigurations, $configurations);

            DB::table('module_configurations')->where('module_id', $module_id)->update([
                'configurations' => serialize($mergedConfigurations),
                'updated_at' => now()
            ]);
        } else {
            DB::table('module_configurations')->insert([
                'module_id' => $module_id,
                'configurations' => serialize($configurations),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $module = DB::table('modules')->where('name', 'MatchMove')->first();

        if ($module) {
            $moduleConfig = DB::table('module_configurations')->where('module_id', $module->id)->first();

            if ($moduleConfig) {
                $existingConfigurations = unserialize($moduleConfig->configurations);

                // Remove the fields if they exist
                unset($existingConfigurations['sandbox_product_id']);
                unset($existingConfigurations['production_product_id']);

                DB::table('module_configurations')->where('module_id', $module->id)->update([
                    'configurations' => serialize($existingConfigurations),
                    'updated_at' => now()
                ]);
            }
        }
    }
}
