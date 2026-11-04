<?php

use App\Models\Currency;
use App\Models\Module;
use App\Models\ModuleConfiguration;
use Illuminate\Database\Migrations\Migration;

class AddAllowedCurrencyToCryptoModule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $module = Module::where('name', 'Crypto')->first();

        if (!$module) {
            $module = new Module();
            $module->name = 'Crypto';
            $module->is_enabled = 0;
            $module->save();
        }

        $configurations = [
            'allowed_fiat_currencies' => [
                'value' => '',
                'type' => 'multiselect',
                'label' => 'Allowed Fiat Currencies',
                'options' => Currency::where('is_crypto', false)->pluck('short')->toArray(),
                'hint' => 'Select the currencies that are allowed for transactions.',
                'sample' => '',
                'doc_url' => '',
            ],
            'allowed_crypto_currencies' => [
                'value' => '',
                'type' => 'multiselect',
                'label' => 'Allowed Crypto Currencies',
                'options' => Currency::where('is_crypto', true)->pluck('short')->toArray(),
                'hint' => 'Select the currencies that are allowed for transactions.',
                'sample' => '',
                'doc_url' => '',
            ],
        ];

        $existingConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($existingConfig) {
            $existingConfigurations = unserialize($existingConfig->configurations);
            $newKeys = array_diff_key($configurations, is_array($existingConfigurations) ? $existingConfigurations : []);
            if (!empty($newKeys)) {
                $existingConfig->configurations = serialize(array_merge($existingConfigurations, $newKeys));
                $existingConfig->save();
            }
        } else {
            $config = new ModuleConfiguration();
            $config->module_id = $module->id;
            $config->configurations = serialize($configurations);
            $config->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $module = Module::where('name', 'Crypto')->first();

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
            'allowed_fiat_currencies',
            'allowed_crypto_currencies',
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
