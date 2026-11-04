<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddZenusModuleWithConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $module = DB::table('modules')->where('name', 'Zenus')->first();

        if ($module) {
            $moduleId = $module->id;
        } else {
            $moduleId = DB::table('modules')->insertGetId([
                'name'       => 'Zenus',
                'is_enabled' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $existingConfig = DB::table('module_configurations')
            ->where('module_id', $moduleId)
            ->first();

        if ($existingConfig) {
            // Configuration already exists – do nothing
            return;
        }

        $configuration = [
            // Production
            'base_url' => [
                'value' => 'https://api.dev.zenus.io/api',
                'type'  => 'text',
                'label' => 'Production Base URL'
            ],
            'auth_base_url' => [
                'value' => 'https://',
                'type'  => 'text',
                'label' => 'Auth Base URL'
            ],
            'app_registrations_name' => [
                'value' => 'xxxxxx',
                'type'  => 'text',
                'label' => 'Production App Registration Name (Full access)'
            ],
            'application_id' => [
                'value' => 'xxxxxxxxx',
                'type'  => 'text',
                'label' => 'Production Application ID'
            ],
            'client_secret' => [
                'value' => 'xxxxxxxxx',
                'type'  => 'password',
                'label' => 'Production Client Secret'
            ],
            'master_account_id' => [
                'value' => 'xxxxxxxx',
                'type'  => 'password',
                'label' => 'Sandbox Master Account'
            ],
            'master_account_id_operation' => [
                'value' => 'xxxxxxxx',
                'type'  => 'password',
                'label' => 'Production Master Account Operation'
            ],
            'scope' => [
                'value' => 'xxxxxx',
                'type'  => 'text',
                'label' => 'Production Scope'
            ],
            'webhook_secret' => [
                'value' => 'xxxxxx',
                'type'  => 'password',
                'label' => 'Production Webhook Secret'
            ],

            // Sandbox
            'sandbox_base_url' => [
                'value' => 'https://api.dev.zenus.io/api',
                'type'  => 'text',
                'label' => 'Sandbox Base URL'
            ],
            'sandbox_auth_base_url' => [
                'value' => 'https://api.dev.zenus.io/login/oauth2/v2.0/token',
                'type'  => 'text',
                'label' => 'Sandbox Auth Base URL'
            ],
            'sandbox_app_registrations_name' => [
                'value' => 'xxxxxxxxx',
                'type'  => 'text',
                'label' => 'Sandbox App Registration Name (Full access)'
            ],
            'sandbox_application_id' => [
                'value' => 'xxxxxx',
                'type'  => 'text',
                'label' => 'Sandbox Application ID'
            ],
            'sandbox_client_secret' => [
                'value' => 'xxxxxx',
                'type'  => 'password',
                'label' => 'Sandbox Client Secret'
            ],
            'sandbox_master_account_id' => [
                'value' => 'ID-11749',
                'type'  => 'password',
                'label' => 'Sandbox Master Account'
            ],
            'sandbox_master_account_id_operation' => [
                'value' => 'ID-11750',
                'type'  => 'password',
                'label' => 'Sandbox Master Account Operation'
            ],
            'sandbox_scope' => [
                'value' => 'xxxxxx',
                'type'  => 'text',
                'label' => 'Sandbox Scope'
            ],
            'sandbox_webhook_secret' => [
                'value' => 'xxxxxx',
                'type'  => 'password',
                'label' => 'Sandbox Webhook Secret'
            ],
        ];

        DB::table('module_configurations')->insert([
            'module_id'     => $moduleId,
            'configurations' => serialize($configuration),
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
        $module = DB::table('modules')->where('name', 'Zenus')->first();

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
