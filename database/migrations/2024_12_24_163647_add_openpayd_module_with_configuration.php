<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddOpenPaydModuleWithConfiguration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Step 1: Insert into the `module` table
        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'OpenPayd',
            'is_enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Step 2: Create configuration array in the required format
        $configuration = [
            'api_base_url' => [
                'value' => 'https://openpayd.com/api',
                'type' => 'text',
                'label' => 'API Base URL'
            ],
            'api_user' => [
                'value' => '',
                'type' => 'text',
                'label' => 'API User'
            ],
            'api_password' => [
                'value' => '',
                'type' => 'password',
                'label' => 'API Password'
            ],
            'sandbox_api_base_url' => [
                'value' => 'https://sandbox.openpayd.com/api',
                'type' => 'text',
                'label' => 'Sandbox API Base URL'
            ],
            'sandbox_api_user' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Sandbox API User'
            ],
            'sandbox_api_password' => [
                'value' => '',
                'type' => 'password',
                'label' => 'Sandbox API Password'
            ],
        ];

        $serializedConfiguration = serialize($configuration);

        // Step 3: Insert into the `module_configuration` table
        DB::table('module_configurations')->insert([
            'module_id' => $moduleId,
            'configurations' => $serializedConfiguration,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Find the `module` record
        $module = DB::table('module')->where('name', 'OpenPayd')->first();

        if ($module) {
            // Delete associated configuration
            DB::table('module_configuration')->where('module_id', $module->id)->delete();
            // Delete the module
            DB::table('module')->where('id', $module->id)->delete();
        }
    }
}
