<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use Illuminate\Database\Migrations\Migration;

class AddFireblockApiKeysFieldsToModuleConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $module = Module::where('name', 'Fireblocks')->first();

        if (!$module) {
            return;
        }

        $configRow = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$configRow) {
            return;
        }

        $newConfigurations = [
            'admin_api_key' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Admin Portal API Key',
            ],
            'admin_api_secret' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Admin Portal API Secret',
            ],
            'admin_api_secret_path' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Admin Portal API Secret Path',
            ],

            
            'mobile_api_key' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Mobile API Key',
            ],
            'mobile_api_secret' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Mobile API Secret',
            ],
            'mobile_api_secret_path' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Mobile API Secret Path',
            ],
        ];

        $existingConfigurations = [];

        if (!empty($configRow->configurations)) {
            $existingConfigurations = @unserialize($configRow->configurations);

            if (!is_array($existingConfigurations)) {
                $existingConfigurations = [];
            }
        }

        $updated = false;

        foreach ($newConfigurations as $key => $configuration) {
            if (!array_key_exists($key, $existingConfigurations)) {
                $existingConfigurations[$key] = $configuration;
                $updated = true;
            }
        }

        if ($updated) {
            ModuleConfiguration::where('id', $configRow->id)->update([
                'configurations' => serialize($existingConfigurations),
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
        $module = Module::where('name', 'Fireblocks')->first();

        if (!$module) {
            return;
        }

        $configRow = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$configRow || empty($configRow->configurations)) {
            return;
        }

        $configurations = @unserialize($configRow->configurations);

        if (!is_array($configurations)) {
            return;
        }

        $keysToRemove = [
            'admin_api_key',
            'admin_api_secret',
            'admin_api_secret_path',
            'mobile_api_key',
            'mobile_api_secret',
            'mobile_api_secret_path',
        ];

        foreach ($keysToRemove as $key) {
            unset($configurations[$key]);
        }

        ModuleConfiguration::where('id', $configRow->id)->update([
            'configurations' => serialize($configurations),
            'updated_at'     => now(),
        ]);
    }
}