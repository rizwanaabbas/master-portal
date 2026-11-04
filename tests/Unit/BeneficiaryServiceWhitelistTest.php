<?php

namespace Tests\Unit;

use App\Modules\Fireblocks\Models\CryptoBeneficiary;
use App\Modules\Fireblocks\Models\CryptoExternalWallet;
use App\Modules\Fireblocks\Services\BeneficiaryService;
use App\Modules\Fireblocks\Services\FireblocksConfigService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BeneficiaryServiceWhitelistTest extends TestCase
{
    private string $originalDatabase;
    private $eventDispatcher;

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

        $this->eventDispatcher = Model::getEventDispatcher();
        Model::unsetEventDispatcher();

        FireblocksConfigService::flush();
        $this->createSchema();
        $this->seedFireblocksModuleWithoutWhitelist();
    }

    protected function tearDown(): void
    {
        Model::setEventDispatcher($this->eventDispatcher);
        FireblocksConfigService::flush();
        DB::purge('sqlite');
        config(['database.default' => $this->originalDatabase]);

        parent::tearDown();
    }

    public function test_find_or_create_external_wallet_returns_operation_unavailable_without_whitelist_creds(): void
    {
        $service = new BeneficiaryService();

        $result = $service->findOrCreateExternalWallet([
            'client_id'       => 99,
            'name'            => 'Client 99 - 99',
            'customer_ref_id' => '99',
            'created_by'      => 1,
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('operation_unavailable', $result['error']);
        $this->assertSame(
            'This operation is not available at the moment. Please contact support.',
            $result['message']
        );
        $this->assertSame(0, CryptoExternalWallet::count());
    }

    public function test_find_or_create_external_wallet_returns_existing_wallet_without_whitelist_creds(): void
    {
        $existing = CryptoExternalWallet::create([
            'client_id'            => 99,
            'fireblocks_wallet_id' => 'fb-wallet-1',
            'name'                 => 'Client 99 - 99',
            'customer_ref_id'      => '99',
            'created_by'           => 1,
        ]);

        $service = new BeneficiaryService();
        $result = $service->findOrCreateExternalWallet([
            'client_id'       => 99,
            'name'            => 'Client 99 - 99',
            'customer_ref_id' => '99',
            'created_by'      => 1,
        ]);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['created']);
        $this->assertSame($existing->id, $result['data']->id);
    }

    public function test_add_wallet_asset_returns_operation_unavailable_without_whitelist_creds(): void
    {
        $wallet = CryptoExternalWallet::create([
            'client_id'            => 99,
            'fireblocks_wallet_id' => 'fb-wallet-1',
            'name'                 => 'Client 99 - 99',
            'customer_ref_id'      => '99',
            'created_by'           => 1,
        ]);

        $service = new BeneficiaryService();
        $result = $service->addWalletAsset($wallet, [
            'account_id'     => 1,
            'label'          => 'Test',
            'wallet_address' => '0xabc',
            'currency_id'    => 1,
            'network_id'     => 1,
            'asset_id'       => 'ETH',
            'asset_type'     => 'crypto',
            'created_by'     => 1,
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('operation_unavailable', $result['error']);
        $this->assertSame(0, CryptoBeneficiary::count());
    }

    public function test_list_for_account_returns_local_data_without_whitelist_creds(): void
    {
        $wallet = CryptoExternalWallet::create([
            'client_id'            => 99,
            'fireblocks_wallet_id' => 'fb-wallet-1',
            'name'                 => 'Client 99 - 99',
            'customer_ref_id'      => '99',
            'created_by'           => 1,
        ]);

        CryptoBeneficiary::create([
            'external_wallet_id'   => $wallet->id,
            'fireblocks_wallet_id' => $wallet->fireblocks_wallet_id,
            'account_id'           => 42,
            'label'                => 'Saved Beneficiary',
            'wallet_address'       => '0xabc',
            'currency_id'          => 1,
            'network_id'           => 1,
            'is_active'            => true,
            'provider'             => 'Fireblocks',
            'asset_type'           => 'crypto',
            'created_by'           => 1,
            'fireblocks_asset_id'  => 'ETH',
            'fireblocks_asset_status' => 'APPROVED',
        ]);

        $service = new BeneficiaryService();
        $result = $service->listForAccount(42);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('Saved Beneficiary', $result['data']->first()->label);
    }

    private function seedFireblocksModuleWithoutWhitelist(): void
    {
        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'Fireblocks',
            'is_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('module_configurations')->insert([
            'module_id' => $moduleId,
            'configurations' => serialize([
                'base_url' => ['value' => 'https://api.fireblocks.io', 'type' => 'text', 'label' => 'Base URL'],
                'admin_api_key' => ['value' => 'admin-key', 'type' => 'text', 'label' => 'Admin API Key'],
                'admin_api_secret' => ['value' => '', 'type' => 'text', 'label' => 'Admin API Secret'],
                'admin_api_secret_path' => ['value' => '/tmp/admin.pem', 'type' => 'text', 'label' => 'Admin Secret Path'],
                'mobile_api_key' => ['value' => 'mobile-key', 'type' => 'text', 'label' => 'Mobile API Key'],
                'mobile_api_secret' => ['value' => '', 'type' => 'text', 'label' => 'Mobile API Secret'],
                'mobile_api_secret_path' => ['value' => '/tmp/mobile.pem', 'type' => 'text', 'label' => 'Mobile Secret Path'],
                'source_vault_account_id' => ['value' => '0', 'type' => 'text', 'label' => 'Source Vault'],
            ]),
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

        Schema::create('crypto_external_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('fireblocks_wallet_id')->nullable();
            $table->string('name');
            $table->string('customer_ref_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('crypto_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_wallet_id')->nullable();
            $table->string('fireblocks_wallet_id')->nullable();
            $table->unsignedBigInteger('account_id');
            $table->string('label');
            $table->string('wallet_address');
            $table->string('destination_tag')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('network_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('provider')->nullable();
            $table->string('asset_type')->nullable();
            $table->text('additional_info')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('fireblocks_asset_id')->nullable();
            $table->string('fireblocks_asset_status')->nullable();
            $table->timestamp('fireblocks_activation_time')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('networks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->timestamps();
        });
    }
}
