<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddLorumModuleWithConfiguration extends Migration
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
                'created_at'=> now(),
                'updated_at'=> now(),
            ]);
        }

        /**
         * STEP 2: Check if configuration already exists
         */
        $existingConfig = DB::table('module_configurations')
            ->where('module_id', $moduleId)
            ->first();

        if ($existingConfig) {
            // Configuration already exists – do nothing
            return;
        }

        /**
         * STEP 3: Define configuration structure
         */
        $configuration = [
            // Production
            'base_url' => [
                'value' => 'https://',
                'type'  => 'text',
                'label' => 'Base URL'
            ],
            'auth_base_url' => [
                'value' => 'https://',
                'type'  => 'text',
                'label' => 'Auth Base URL'
            ],
            'client_id' => [
                'value' => 'xxxxxxxxx',
                'type'  => 'text',
                'label' => 'Client ID'
            ],
            'client_secret' => [
                'value' => 'xxxxxxxxx',
                'type'  => 'password',
                'label' => 'Client Secret'
            ],
            'usd_primary_account_id' => [
                'value' => 'xxxxxxxxx',
                'type'  => 'password',
                'label' => 'USD Primary Account ID'
            ],
            'eur_primary_account_id' => [
                'value' => 'xxxxxxxxx',
                'type'  => 'password',
                'label' => 'EUR Primary Account ID'
            ],

            // Sandbox
            'sandbox_base_url' => [
                'value' => 'https://api-sandbox.fuse.me',
                'type'  => 'text',
                'label' => 'Sandbox Base URL'
            ],
            'sandbox_auth_base_url' => [
                'value' => 'https://auth-sandbox.fuse.me',
                'type'  => 'text',
                'label' => 'Sandbox Auth Base URL'
            ],
            'sandbox_client_id' => [
                'value' => 'xxxxxx',
                'type'  => 'text',
                'label' => 'Sandbox Client ID'
            ],
            'sandbox_client_secret' => [
                'value' => 'xxxxx',
                'type'  => 'password',
                'label' => 'Sandbox Client Secret'
            ],
            'sandbox_usd_primary_account_id' => [
                'value' => 'xxxxxxxxx',
                'type'  => 'password',
                'label' => 'Sandbox USD Primary Account ID'
            ],
            'sandbox_eur_primary_account_id' => [
                'value' => 'xxxxxxxxx',
                'type'  => 'password',
                'label' => 'Sandbox EUR Primary Account ID'
            ],

            // General
            // 'allowed_currencies' => [
            //     'value' => 'USD,AED',
            //     'type'  => 'text',
            //     'label' => 'Allowed Currencies'
            // ],
        ];

        /**
         * STEP 4: Insert configuration
         */
        DB::table('module_configurations')->insert([
            'module_id'     => $moduleId,
            'configurations'=> serialize($configuration),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $module = DB::table('modules')->where('name', 'Lorum')->first();

        if ($module) {
            DB::table('module_configurations')
                ->where('module_id', $module->id)
                ->delete();

            DB::table('modules')
                ->where('id', $module->id)
                ->delete();
        }
    }
}
