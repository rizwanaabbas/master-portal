<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;
use App\Models\ModuleConfiguration;

return new class extends Migration {
    public function up()
    {
        $module = Module::where('name', 'CoinGecko')->first();

        if (!$module) {
            $module = new Module();
            $module->name = 'CoinGecko';
            $module->is_enabled = 0;
            $module->save();
        }

        $configurations = [

            // ---------------------------------------------------------------
            // Connection
            // ---------------------------------------------------------------
            'enabled' => [
                'value' => '0',
                'type'  => 'checkbox',
                'label' => 'Enable CoinGecko Integration',
            ],
            'integration_mode' => [
                'value' => 'rest',
                'type'  => 'select',
                'label' => 'Integration Mode',
                'options' => [
                    'rest'      => 'REST Only',
                    'websocket' => 'WebSocket Only',
                    'hybrid'    => 'Hybrid (REST + WebSocket)',
                ],
            ],
            'api_base_url' => [
                'value' => 'https://api.coingecko.com/api/v3',
                'type'  => 'text',
                'label' => 'API Base URL',
            ],
            'use_pro_api' => [
                'value' => '0',
                'type'  => 'checkbox',
                'label' => 'Use Pro API (pro-api.coingecko.com)',
            ],
            'api_key' => [
                'value' => '',
                'type'  => 'password',
                'label' => 'CoinGecko API Key',
            ],
            'connect_timeout' => [
                'value' => '10',
                'type'  => 'number',
                'label' => 'Connect Timeout (seconds)',
            ],
            'timeout' => [
                'value' => '30',
                'type'  => 'number',
                'label' => 'Request Timeout (seconds)',
            ],
            'max_retries' => [
                'value' => '3',
                'type'  => 'number',
                'label' => 'Max Retries on Failure',
            ],
            'retry_delay' => [
                'value' => '500',
                'type'  => 'number',
                'label' => 'Retry Delay (milliseconds)',
            ],

            // ---------------------------------------------------------------
            // Data Refresh
            // ---------------------------------------------------------------
            'realtime_enabled' => [
                'value' => '0',
                'type'  => 'checkbox',
                'label' => 'Enable Real-Time Price Updates',
            ],
            'websocket_reconnect_interval' => [
                'value' => '5',
                'type'  => 'number',
                'label' => 'WebSocket Reconnect Interval (seconds)',
            ],
            'rest_polling_interval' => [
                'value' => '15',
                'type'  => 'number',
                'label' => 'REST Polling Interval (seconds)',
            ],
            'cache_duration' => [
                'value' => '60',
                'type'  => 'number',
                'label' => 'Cache Duration (seconds)',
            ],

            // ---------------------------------------------------------------
            // Market Data
            // ---------------------------------------------------------------
            'default_currency' => [
                'value' => 'usd',
                'type'  => 'text',
                'label' => 'Default Currency',
            ],
            'supported_currencies' => [
                'value' => 'usd,eur,gbp',
                'type'  => 'text',
                'label' => 'Supported Currencies (comma separated)',
            ],
            'supported_assets' => [
                'value' => 'bitcoin,ethereum,tether,binancecoin,usd-coin,ripple,solana,tron',
                'type'  => 'textarea',
                'label' => 'Supported Assets — CoinGecko Coin IDs (comma separated)',
            ],
            'dashboard_assets' => [
                'value' => 'bitcoin,ethereum,tether,binancecoin,usd-coin,ripple,solana,tron',
                'type'  => 'textarea',
                'label' => 'Assets Displayed on Dashboard (comma separated)',
            ],
            'dashboard_coin_limit' => [
                'value' => '7',
                'type'  => 'number',
                'label' => 'Number of Coins to Display (Market Prices Table)',
            ],
            'update_frequency' => [
                'value' => '15',
                'type'  => 'number',
                'label' => 'Data Update Frequency (seconds)',
            ],

            // ---------------------------------------------------------------
            // Feature Controls
            // ---------------------------------------------------------------
            'show_market_summary' => [
                'value' => '1',
                'type'  => 'checkbox',
                'label' => 'Show Market Summary (Market Cap / Volume)',
            ],
            'show_market_prices_table' => [
                'value' => '1',
                'type'  => 'checkbox',
                'label' => 'Show Market Prices Table',
            ],
            'show_trending' => [
                'value' => '1',
                'type'  => 'checkbox',
                'label' => 'Show Trending Coins',
            ],
            'show_portfolio_breakdown' => [
                'value' => '1',
                'type'  => 'checkbox',
                'label' => 'Show Portfolio Breakdown Pricing',
            ],
            'show_coin_detail_chart' => [
                'value' => '1',
                'type'  => 'checkbox',
                'label' => 'Show Coin Detail Chart',
            ],
            'show_btc_converter' => [
                'value' => '1',
                'type'  => 'checkbox',
                'label' => 'Show BTC to USD Converter',
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

    public function down()
    {
        $module = Module::where('name', 'CoinGecko')->first();

        if ($module) {
            ModuleConfiguration::where('module_id', $module->id)->delete();
            $module->delete();
        }
    }
};
