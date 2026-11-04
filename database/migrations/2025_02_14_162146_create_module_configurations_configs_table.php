<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CreateModuleConfigurationsConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if module exists
        $module = DB::table('modules')->where('name', 'Bitpace')->first();

        if (!$module) {
            // Create module if not exists
            $module_id = DB::table('modules')->insertGetId([
                'name' => 'Bitpace',
                'is_enabled' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $module_id = $module->id;
        }

        // Configurations to insert/update
        $configurations = [
            "sandbox_api_merchant_url" => [
                "value" => "https://api-sandbox.bitpace.com/api/v1",
                "type" => "text",
                "label" => "Sand Box API URL"
            ],
            "sandbox_api_merchant_code" => [
                "value" => "10425112538",
                "type" => "text",
                "label" => "Sandbox API Merchant Code"
            ],
            "sandbox_api_merchant_password" => [
                "value" => "fe89292d-f9c6-4c32-a7a0-1c53312201b6",
                "type" => "text",
                "label" => "Sandbox API Merchant Password"
            ],
            "sandbox_api_merchant_callback_secret" => [
                "value" => "xxxxxx",
                "type" => "text",
                "label" => "Sandbox API Merchant callback Secret"
            ],
            "sandbox_excluded_users" => [
                "value" => "122",
                "type" => "text",
                "label" => "Sandbox Excluded Users"
            ],
            "production_api_merchant_url" => [
                "value" => "xxxxx",
                "type" => "text",
                "label" => "Production API URL"
            ],
            "production_api_merchant_code" => [
                "value" => "xxxxx",
                "type" => "text",
                "label" => "Production API Merchant Code"
            ],
            "production_api_merchant_password" => [
                "value" => "xxxxxx",
                "type" => "text",
                "label" => "Production API Merchant Password"
            ],
            "production_api_merchant_callback_secret" => [
                "value" => "xxxxxx",
                "type" => "text",
                "label" => "Production API Merchant callback Secret"
            ],
            "production_excluded_users" => [
                "value" => "22",
                "type" => "text",
                "label" => "Production Excluded Users"
            ],
            "chainanalysis_api_key" => [
                "value" => "fd2b15b0446b9b1f76de2676aa64cd935808fb14f028c44b3b7fdf91521626ef",
                "type" => "text",
                "label" => "Chain Analysis Api Key"
            ],
            "allowed_withdraw_currencies" => [
                "value" => "USDT",
                "type" => "text",
                "label" => "Allowed Withdraw Currencies (comma separated)"
            ],
            "comission_percentage_to_hold" => [
                "value" => "5",
                "type" => "text",
                "label" => "Comission percentage to hold"
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       // Schema::dropIfExists('module_configurations_configs');
    }
}
