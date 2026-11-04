<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddProcessPayoutFromChildToZenusBankConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $module = DB::table('modules')->where('name', 'Zenus')->first();

        $moduleId = $module->id ?? DB::table('modules')->insertGetId([
            'name'       => 'Zenus',
            'is_enabled' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $existingConfigRow = DB::table('module_configurations')
            ->where('module_id', $moduleId)
            ->first();

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

                DB::table('module_configurations')
                    ->where('id', $existingConfigRow->id)
                    ->update([
                        'configurations' => serialize($existingConfig),
                        'updated_at'     => now(),
                    ]);
            }
        } else {
            DB::table('module_configurations')->insert([
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
        $module = DB::table('modules')->where('name', 'Zenus')->first();

        if (!$module) {
            return;
        }

        $configRow = DB::table('module_configurations')
            ->where('module_id', $module->id)
            ->first();

        if (!$configRow || empty($configRow->configurations)) {
            return;
        }

        $config = @unserialize($configRow->configurations) ?: [];

        // Remove only the key we added
        unset($config['process_payout_from_child']);

        DB::table('module_configurations')
            ->where('id', $configRow->id)
            ->update([
                'configurations' => serialize($config),
                'updated_at'     => now(),
            ]);
    }
}
