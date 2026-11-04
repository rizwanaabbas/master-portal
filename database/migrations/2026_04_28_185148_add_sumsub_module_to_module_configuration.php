<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddSumsubModuleToModuleConfiguration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $module = DB::table('modules')->where('name', 'Sumsub')->first();

        if ($module) {
            $moduleId = $module->id;
        } else {
            $moduleId = DB::table('modules')->insertGetId([
                'name'       => 'Sumsub',
                'is_enabled' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $existingConfig = DB::table('module_configurations')
            ->where('module_id', $moduleId)
            ->first();

        if ($existingConfig) {
            return;
        }

        $configuration = [
            // Production
            'production_app_token' => [
                'value' => 'xxxxxxxxx',
                'type'  => 'text',
                'label' => 'Production App Token'
            ],
            'production_secret_key' => [
                'value' => 'xxxxxxxxx',
                'type'  => 'password',
                'label' => 'Production Secret Key'
            ],
            'production_time_to_leave' => [
                'value' => '600',
                'type'  => 'number',
                'label' => 'Production Time to Leave (In Seconds)'
            ],

            // Sandbox
            'sandbox_app_token' => [
                'value' => 'xxxxxxxxx',
                'type'  => 'text',
                'label' => 'Sandbox App Token'
            ],
            'sandbox_secret_key' => [
                'value' => 'xxxxxxxxx',
                'type'  => 'password',
                'label' => 'Sandbox Secret Key'
            ],
            'sandbox_time_to_leave' => [
                'value' => '600',
                'type'  => 'number',
                'label' => 'Sandbox Time to Leave (In Seconds)'
            ],

            // Common
            'base_url' => [
                'value' => 'https://api.sumsub.com',
                'type'  => 'text',
                'label' => 'Base URL'
            ],
            'endpoint' => [
                'value' => '/resources/accessTokens/sdk?levelName=',
                'type'  => 'text',
                'label' => 'Endpoint'
            ],
            'sumsub_webhook_secret' => [
                'value' => 'xxxxxxxxx',
                'type'  => 'password',
                'label' => 'Sumsub Webhook Secret'
            ],
            'verification_level_individual_client' => [
                'value' => 'individual-onboarding',
                'type'  => 'text',
                'label' => 'Verification Level for Individual Clients'
            ],
            'verification_level_company_client' => [
                'value' => 'company-onboarding',
                'type'  => 'text',
                'label' => 'Verification Level for Company Clients'
            ],
            'verification_level_sub_account_individual_client' => [
                'value' => 'sub-account-individual',
                'type'  => 'text',
                'label' => 'Verification Level for Sub-Account Individual Clients'
            ],
            'verification_level_sub_account_company_client' => [
                'value' => 'sub-account-company',
                'type'  => 'text',
                'label' => 'Verification Level for Sub-Account Company Clients'
            ],
            'verification_level_selfie' => [
                'value' => 'liveness-link',
                'type'  => 'text',
                'label' => 'Verification Level for Selfie'
            ],
            'verification_level_passport' => [
                'value' => 'passport-link',
                'type'  => 'text',
                'label' => 'Verification Level for Passport'
            ],
            'verification_level_passport_and_selfie' => [
                'value' => 'passport-n-liveness-link',
                'type'  => 'text',
                'label' => 'Verification Level for Passport and Selfie'
            ],
        ];

        DB::table('module_configurations')->insert([
            'module_id'   => $moduleId,
            'configurations' => serialize($configuration),
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
        $module = DB::table('modules')->where('name', 'Sumsub')->first();

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
