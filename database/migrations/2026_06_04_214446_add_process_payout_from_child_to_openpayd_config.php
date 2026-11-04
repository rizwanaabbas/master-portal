<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddProcessPayoutFromChildToOpenpaydConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $module = Module::where('name', 'OpenPayd')->first();

        $moduleId = $module->id ?? Module::insertGetId([
            'name'       => 'OpenPayd',
            'is_enabled' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $existingConfigRow = ModuleConfiguration::where('module_id', $moduleId)->first();

        $newKey = 'process_payout_from_child';

        $newConfig = [
            $newKey => [
                'value' => false,
                'type'  => 'checkbox',
                'label' => 'Process Payout From Child'
            ],
        ];

        if ($existingConfigRow) {

            $existingConfig = [];
            if (!empty($existingConfigRow->configurations)) {
                $existingConfig = @unserialize($existingConfigRow->configurations) ?: [];
            }

            // Avoid overwrite if already exists
            if (!array_key_exists($newKey, $existingConfig)) {
                $existingConfig[$newKey] = $newConfig[$newKey];

                ModuleConfiguration::where('id', $existingConfigRow->id)
                    ->update([
                        'configurations' => serialize($existingConfig),
                        'updated_at'     => now(),
                    ]);
            }
        } else {
            ModuleConfiguration::insert([
                'module_id'      => $moduleId,
                'configurations' => serialize($newConfig),
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
        $module = Module::where('name', 'OpenPayd')->first();

        if (!$module) {
            return;
        }

        $configRow = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$configRow || empty($configRow->configurations)) {
            return;
        }

        $config = @unserialize($configRow->configurations) ?: [];

        // Remove only the key we added
        unset($config['process_payout_from_child']);

        ModuleConfiguration::where('id', $configRow->id)
            ->update([
                'configurations' => serialize($config),
                'updated_at'     => now(),
            ]);
    }
}
