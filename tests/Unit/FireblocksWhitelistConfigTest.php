<?php

namespace Tests\Unit;

use App\Modules\Fireblocks\Services\FireblocksConfigService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class FireblocksWhitelistConfigTest extends TestCase
{
    private string $originalDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDatabase = config('database.default');
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('sqlite');
        DB::connection('sqlite')->getPdo();

        FireblocksConfigService::flush();
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        FireblocksConfigService::flush();
        DB::purge('sqlite');
        config(['database.default' => $this->originalDatabase]);

        parent::tearDown();
    }

    public function test_has_whitelist_credentials_is_false_when_not_configured(): void
    {
        $this->seedFireblocksModule([]);

        $this->assertFalse(FireblocksConfigService::hasWhitelistCredentials());
        $this->assertSame('', FireblocksConfigService::whitelistApiKey());
        $this->assertSame('', FireblocksConfigService::whitelistApiSecretPath());
    }

    public function test_has_whitelist_credentials_is_true_when_key_and_secret_file_exist(): void
    {
        $secretPath = tempnam(sys_get_temp_dir(), 'fb-whitelist-');
        file_put_contents($secretPath, 'dummy-secret');

        try {
            $this->seedFireblocksModule([
                'whitelist_address_api' => [
                    'value' => 'whitelist-key',
                    'type'  => 'text',
                    'label' => 'Whitelist Address API Key',
                ],
                'whitelist_address_api_secret_path' => [
                    'value' => $secretPath,
                    'type'  => 'text',
                    'label' => 'Whitelist Address API Secret Path',
                ],
            ]);

            $this->assertTrue(FireblocksConfigService::hasWhitelistCredentials());
            $this->assertSame('whitelist-key', FireblocksConfigService::whitelistApiKey());
            $this->assertSame($secretPath, FireblocksConfigService::whitelistApiSecretPath());
        } finally {
            @unlink($secretPath);
        }
    }

    public function test_resolved_config_includes_whitelist_fields(): void
    {
        $this->seedFireblocksModule([
            'whitelist_address_api' => [
                'value' => 'resolved-key',
                'type'  => 'text',
                'label' => 'Whitelist Address API Key',
            ],
            'whitelist_address_api_secret_path' => [
                'value' => '/tmp/whitelist.pem',
                'type'  => 'text',
                'label' => 'Whitelist Address API Secret Path',
            ],
        ]);

        $config = FireblocksConfigService::get();

        $this->assertSame('resolved-key', $config['whitelist_api_key']);
        $this->assertSame('/tmp/whitelist.pem', $config['whitelist_api_secret_path']);
    }

    private function seedFireblocksModule(array $extraConfig): void
    {
        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'Fireblocks',
            'is_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $baseConfig = [
            'base_url' => ['value' => 'https://api.fireblocks.io', 'type' => 'text', 'label' => 'Base URL'],
            'admin_api_key' => ['value' => 'admin-key', 'type' => 'text', 'label' => 'Admin API Key'],
            'admin_api_secret' => ['value' => '', 'type' => 'text', 'label' => 'Admin API Secret'],
            'admin_api_secret_path' => ['value' => '/tmp/admin.pem', 'type' => 'text', 'label' => 'Admin Secret Path'],
            'mobile_api_key' => ['value' => 'mobile-key', 'type' => 'text', 'label' => 'Mobile API Key'],
            'mobile_api_secret' => ['value' => '', 'type' => 'text', 'label' => 'Mobile API Secret'],
            'mobile_api_secret_path' => ['value' => '/tmp/mobile.pem', 'type' => 'text', 'label' => 'Mobile Secret Path'],
            'source_vault_account_id' => ['value' => '0', 'type' => 'text', 'label' => 'Source Vault'],
        ];

        DB::table('module_configurations')->insert([
            'module_id' => $moduleId,
            'configurations' => serialize(array_merge($baseConfig, $extraConfig)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('module_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->text('configurations')->nullable();
            $table->timestamps();
        });
    }
}
