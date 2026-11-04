<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\ModuleConfiguration;

return new class extends Migration {
    public function up()
    {
        $module = Module::where('name', 'FeeEngine')->first();

        if (!$module) {
            $module = new Module();
            $module->name = 'FeeEngine';
            $module->is_enabled = 0; // master switch — toggled from Settings > Modules
            $module->save();
        }

        $configurations = [
            'crypto_fee_display' => [
                'value'   => 'summed',
                'type'    => 'select',
                'label'   => 'Crypto Fee Display',
                'options' => ['separate' => 'Separate (itemized)', 'summed' => 'Summed (single number)'],
            ],
            'default_fee_tier_group_id' => [
                'value'          => '',
                'type'           => 'select',
                'label'          => 'Default Fee Tier Group (fallback)',
                'options_source' => 'fee_tier_groups', // populated from DB in the editor
            ],
            'applicable_crypto' => [
                'value' => '1',
                'type'  => 'checkbox',
                'label' => 'Apply fees to Crypto currencies',
            ],
            'applicable_fiat' => [
                'value' => '0',
                'type'  => 'checkbox',
                'label' => 'Apply fees to Fiat currencies',
            ],
            'rounding_mode' => [
                'value'   => 'half_up',
                'type'    => 'select',
                'label'   => 'Rounding Mode',
                'options' => ['half_up' => 'Half Up', 'half_down' => 'Half Down', 'up' => 'Up (ceil)', 'down' => 'Down (floor)'],
            ],
            'rounding_precision_crypto' => [
                'value' => '8',
                'type'  => 'number',
                'label' => 'Rounding Precision (crypto)',
            ],
            'rounding_precision_fiat' => [
                'value' => '2',
                'type'  => 'number',
                'label' => 'Rounding Precision (fiat)',
            ],
            'block_on_missing_tier' => [
                'value' => '0',
                'type'  => 'checkbox',
                'label' => 'Block transaction when no matching fee tier (fail closed)',
            ],
            'integrate_fee_helper_crypto' => [
                'value' => '0',
                'type'  => 'checkbox',
                'label' => 'Integrate existing FeeHelper for Crypto',
            ],
            'integrate_fee_helper_fiat' => [
                'value' => '0',
                'type'  => 'checkbox',
                'label' => 'Integrate existing FeeHelper for Fiat',
            ],
        ];

        $existingConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($existingConfig) {
            $existingConfigurations = unserialize($existingConfig->configurations);
            $merged = [];
            // Overwrite field definitions (type/label/options) but keep any value
            // an operator already set.
            foreach ($configurations as $key => $def) {
                if (isset($existingConfigurations[$key]['value'])) {
                    $def['value'] = $existingConfigurations[$key]['value'];
                }
                $merged[$key] = $def;
            }
            $existingConfig->configurations = serialize($merged);
            $existingConfig->save();
        } else {
            $config = new ModuleConfiguration();
            $config->module_id = $module->id;
            $config->configurations = serialize($configurations);
            $config->save();
        }
    }

    public function down()
    {
        $module = Module::where('name', 'FeeEngine')->first();

        if ($module) {
            ModuleConfiguration::where('module_id', $module->id)->delete();
            $module->delete();
        }
    }
};
