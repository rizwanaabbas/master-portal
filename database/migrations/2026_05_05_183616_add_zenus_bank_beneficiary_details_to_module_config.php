<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddZenusBankBeneficiaryDetailsToModuleConfig extends Migration
{
    public function up()
    {
        $module = DB::table('modules')->where('name', 'Zenus')->first();

        $moduleId = optional($module)->id;

        if (!$moduleId) {
            $moduleId = DB::table('modules')->insertGetId([
                'name'       => 'Zenus',
                'is_enabled' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $existingConfigRow = DB::table('module_configurations')
            ->where('module_id', $moduleId)
            ->first();

        $newConfig = [
            'bank_name' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Bank Name',
            ],
            'bank_address' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Bank Address',
            ],
            'master_vrn' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Master Bank Account VRN',
            ],
            'master_ach_routing_number' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Master Account ACH Routing Number',
            ],
            'master_fed_wire_routing_number' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Master Account FED Wire Routing Number',
            ],
            'master_account_routing_number' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Master Account Routing Number',
            ],
            'master_account_bic' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Master Account BIC',
            ],
            'master_bank_intermediatry_bank_name' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Master Intermediary Bank Name',
            ],
            'master_bank_intermediatry_routing_number' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Master Intermediary Bank Routing Number',
            ],
            'master_bank_intermediatry_bic' => [
                'value' => '',
                'type'  => 'text',
                'label' => 'Master Intermediary Bank BIC',
            ],
        ];

        // ---------------------------
        // MERGE LOGIC (NO OVERRIDE)
        // ---------------------------

        $existingConfig = [];

        if ($existingConfigRow && !empty($existingConfigRow->configurations)) {
            $decoded = @unserialize($existingConfigRow->configurations);

            if (is_array($decoded)) {
                $existingConfig = $decoded;
            }
        }

        // existing keys win, new keys only added if missing
        $mergedConfig = $existingConfig + $newConfig;

        if ($existingConfigRow) {
            DB::table('module_configurations')
                ->where('id', $existingConfigRow->id)
                ->update([
                    'configurations' => serialize($mergedConfig),
                    'updated_at'     => now(),
                ]);
        } else {
            DB::table('module_configurations')->insert([
                'module_id'      => $moduleId,
                'configurations' => serialize($mergedConfig),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

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

        $config = @unserialize($configRow->configurations);

        if (!is_array($config)) {
            return;
        }

        $keysToRemove = [
            'bank_name',
            'bank_address',
            'master_vrn',
            'master_ach_routing_number',
            'master_fed_wire_routing_number',
            'master_account_routing_number',
            'master_account_bic',
            'master_bank_intermediatry_bank_name',
            'master_bank_intermediatry_routing_number',
            'master_bank_intermediatry_bic',
        ];

        foreach ($keysToRemove as $key) {
            unset($config[$key]);
        }

        DB::table('module_configurations')
            ->where('id', $configRow->id)
            ->update([
                'configurations' => serialize($config),
                'updated_at'     => now(),
            ]);
    }
}