<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\ModuleConfiguration;

class AddSumsubClientIdToModuleConfiguration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $module = Module::where('name', 'Sumsub')->first();

        if (!$module) {
            return;
        }

        $configRow = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$configRow || empty($configRow->configurations)) {
            return;
        }

        $config = unserialize($configRow->configurations);

        if (!is_array($config)) {
            $config = [];
        }

        if (!array_key_exists('sumsub_client_id', $config)) {
            $config['sumsub_client_id'] = [
                'value' => 'monfibank.com_167755',
                'type'  => 'text',
                'label' => 'Sumsub Client ID'
            ];

            $configRow->configurations = serialize($config);
            $configRow->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $module = Module::where('name', 'Sumsub')->first();

        if (!$module) {
            return;
        }

        $configRow = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$configRow || empty($configRow->configurations)) {
            return;
        }

        $config = unserialize($configRow->configurations);

        if (is_array($config) && array_key_exists('sumsub_client_id', $config)) {
            unset($config['sumsub_client_id']);

            $configRow->configurations = serialize($config);
            $configRow->save();
        }
    }
}
