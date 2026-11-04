<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddLorumAllowedIpsToLorumModule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * STEP 1: Create or fetch module
         */
        $module = DB::table('modules')->where('name', 'Lorum')->first();

        if ($module) {
            $moduleId = $module->id;
        } else {
            $moduleId = DB::table('modules')->insertGetId([
                'name'       => 'Lorum',
                'is_enabled' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        /**
         * STEP 2: Fetch existing configuration if exists
         */
        $existingConfigRow = DB::table('module_configurations')
            ->where('module_id', $moduleId)
            ->first();

        $sandbox_ips = [
            '15.185.183.112',
            '157.241.63.92',
            '157.241.38.195',
        ];
            
        $production_ips = [
            '16.62.35.28',
            '16.63.177.47',
            '16.63.180.53',
        ];

        // Prepare new configuration to add
        $newConfigKeys = [
            'sandbox_web_hook_ips' => [
                'value' => json_encode($sandbox_ips),
                'type'  => 'text',
                'label' => 'Sandbox Web-Hook Ips'
            ],
            'production_web_hook_ips' => [
                'value' => json_encode($production_ips),
                'type'  => 'text',
                'label' => 'Production Web-Hook Ips'
            ]
        ];

        if ($existingConfigRow) {
            // STEP 3: Merge with existing configuration
            $existingConfig = @unserialize($existingConfigRow->configurations) ?: [];
            $mergedConfig = array_merge($existingConfig, $newConfigKeys);

            // STEP 4: Update the existing row
            DB::table('module_configurations')
                ->where('id', $existingConfigRow->id)
                ->update([
                    'configurations' => serialize($mergedConfig),
                    'updated_at'     => now(),
                ]);
        } else {
            // STEP 4: Insert new configuration
            DB::table('module_configurations')->insert([
                'module_id'      => $moduleId,
                'configurations' => serialize($newConfigKeys),
                'created_at'     => now(),
                'updated_at'     => now(),
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
        $module = DB::table('modules')->where('name', 'Lorum')->first();

        if (!$module) {
            return;
        }

        $configRow = DB::table('module_configurations')
            ->where('module_id', $module->id)
            ->first();

        if (!$configRow) {
            return;
        }

        $config = @unserialize($configRow->configurations) ?: [];

        // Remove only the keys added by this migration
        unset($config['sandbox_web_hook_ips'], $config['production_web_hook_ips']);

        DB::table('module_configurations')
            ->where('id', $configRow->id)
            ->update([
                'configurations' => serialize($config),
                'updated_at'     => now(),
            ]);
    }

}
