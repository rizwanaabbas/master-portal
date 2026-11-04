<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\ModuleConfiguration;

return new class extends Migration {
    public function up()
    {
        $module = Module::where('name', 'Fireblocks')->first();

        if (!$module) {
            return;
        }

        $newConfig = [
            'wallet_pool_id' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Wallet Pool ID',
            ],
        ];

        $configRecord = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($configRecord) {
            $existingConfigurations = unserialize($configRecord->configurations) ?: [];

            // Add the key only if it doesn't already exist to preserve saved values
            if (!array_key_exists('wallet_pool_id', $existingConfigurations)) {
                $updatedConfigurations = array_merge($existingConfigurations, $newConfig);
                $configRecord->configurations = serialize($updatedConfigurations);
                $configRecord->save();
            }
        } else {
            $configRecord = new ModuleConfiguration();
            $configRecord->module_id = $module->id;
            $configRecord->configurations = serialize($newConfig);
            $configRecord->save();
        }
    }

    public function down()
    {
        $module = Module::where('name', 'Fireblocks')->first();

        if (!$module) {
            return;
        }

        $configRecord = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($configRecord) {
            $existingConfigurations = unserialize($configRecord->configurations) ?: [];

            // Remove only the wallet_pool_id key without affecting other configurations
            if (array_key_exists('wallet_pool_id', $existingConfigurations)) {
                unset($existingConfigurations['wallet_pool_id']);
                $configRecord->configurations = serialize($existingConfigurations);
                $configRecord->save();
            }
        }
    }
};