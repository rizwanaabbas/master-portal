<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        // Check if module exists
        $module = DB::table('modules')->where('name', 'E-Money')->first();

        if (!$module) {
            // Create module if not exists
            $module_id = DB::table('modules')->insertGetId([
                'name' => 'E-Money',
                'is_enabled' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $module_id = $module->id;
        }

        // E-Money configuration data
        $configurations = [
            // Sandbox values
            "sandbox_api_base_url" => [
                "value" => "https://testfingateapi.paydwith.com/fingate/integrationapi/v1.0",
                "type" => "text",
                "label" => "Sandbox API Base URL"
            ],
            "sandbox_api_key" => [
                "value" => "xxxx-sandbox-api-key",
                "type" => "text",
                "label" => "Sandbox API Key"
            ],
            "sandbox_secret_key" => [
                "value" => "xxxx-sandbox-secret-key",
                "type" => "text",
                "label" => "Sandbox Secret Key"
            ],
            "sandbox_master_wallet_id" => [
                "value" => "xxxx-sandbox-wallet-id",
                "type" => "text",
                "label" => "Sandbox Master Wallet ID"
            ],
            "sandbox_decryption_key_id" => [
                "value" => "xxxx",
                "type" => "text",
                "label" => "Sandbox Decryption Key ID"
            ],
            
            "sandbox_product_id" => [
                "value" => "xxxx",
                "type" => "text",
                "label" => "Sandbox Product Id"
            ],
            
            // Production values (masked for now)
            "production_api_base_url" => [
                "value" => "xxxx",
                "type" => "text",
                "label" => "Production API Base URL"
            ],
            "production_api_key" => [
                "value" => "xxxx",
                "type" => "text",
                "label" => "Production API Key"
            ],
            "production_secret_key" => [
                "value" => "xxxx",
                "type" => "text",
                "label" => "Production Secret Key"
            ],
            "production_master_wallet_id" => [
                "value" => "xxxx",
                "type" => "text",
                "label" => "Production Master Wallet ID"
            ],
            "production_decryption_key_id" => [
                "value" => "xxxx",
                "type" => "text",
                "label" => "Production Decryption Key ID"
            ],
            
             "production_product_id" => [
                "value" => "xxxx",
                "type" => "text",
                "label" => "Production Product Id"
            ],
            
            "provider_id" => [
                "value" => "3",
                "type" => "text",
                "label" => "Provider ID"
            ],
            "partner_name" => [
                "value" => "FinGate",
                "type" => "text",
                "label" => "Partner Name"
            ],
        ];

        // Check if module configuration already exists
        $existingConfig = DB::table('module_configurations')->where('module_id', $module_id)->first();

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

    public function down()
    {
        $module = DB::table('modules')->where('name', 'E-Money')->first();

        if ($module) {
            DB::table('module_configurations')->where('module_id', $module->id)->delete();
            DB::table('modules')->where('id', $module->id)->delete();
        }
    }
};
