<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\CryptoAddress;
use App\Modules\Fireblocks\Models\Asset;
use App\Modules\Fireblocks\Models\FireblocksVaultAccount;
use App\Modules\Fireblocks\Models\Network;
use App\Modules\Fireblocks\Services\DepositAddressService;
use App\Modules\Fireblocks\Services\VaultDepositAddressSyncService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class VaultDepositAddressSyncServiceTest extends TestCase
{
    private $eventDispatcher;
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

        $this->eventDispatcher = Model::getEventDispatcher();
        Model::unsetEventDispatcher();

        $this->createSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Model::setEventDispatcher($this->eventDispatcher);
        DB::purge('sqlite');
        config(['database.default' => $this->originalDatabase]);

        parent::tearDown();
    }

    public function test_syncs_all_supported_assets_for_vault(): void
    {
        $depositService = Mockery::mock(DepositAddressService::class);
        $depositService->shouldReceive('listDepositAddressesFromSdk')
            ->once()
            ->with('vault-1', 'BTC_TEST')
            ->andReturn([
                'success' => true,
                'data' => [[
                    'address' => 'btc-address-1',
                    'tag' => '',
                    'customerRefId' => '1',
                ]],
            ]);
        $depositService->shouldReceive('listDepositAddressesFromSdk')
            ->once()
            ->with('vault-1', 'ETH_TEST5')
            ->andReturn([
                'success' => true,
                'data' => [[
                    'address' => 'eth-address-1',
                    'tag' => '',
                    'customerRefId' => '2',
                ]],
            ]);

        $service = new TestableVaultDepositAddressSyncService($depositService);
        $service->vaultAssetIds = collect(['BTC_TEST', 'ETH_TEST5', 'UNSUPPORTED_ASSET']);

        $result = $service->syncVaultDepositAddresses('vault-1', [
            'accountName' => 'client-vault-10',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['summary']['assets_checked']);
        $this->assertSame(2, $result['summary']['assets_synced']);
        $this->assertSame(2, $result['summary']['addresses_created']);
        $this->assertSame(2, CryptoAddress::count());
        $this->assertDatabaseHas('crypto_addresses', [
            'asset_id' => 'BTC_TEST',
            'crypto_address' => 'btc-address-1',
            'account_id' => 1,
        ]);
        $this->assertDatabaseHas('crypto_addresses', [
            'asset_id' => 'ETH_TEST5',
            'crypto_address' => 'eth-address-1',
            'account_id' => 2,
        ]);
    }

    public function test_sync_is_idempotent_for_existing_addresses(): void
    {
        CryptoAddress::create([
            'account_id' => 1,
            'provider' => 'fireblocks',
            'asset_id' => 'BTC_TEST',
            'vault_account_id' => 'vault-1',
            'crypto_address' => 'btc-address-1',
            'network_id' => 1,
            'network' => 'Bitcoin Testnet',
        ]);

        $depositService = Mockery::mock(DepositAddressService::class);
        $depositService->shouldReceive('listDepositAddressesFromSdk')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [[
                    'address' => 'btc-address-1',
                    'tag' => '',
                    'customerRefId' => '1',
                ]],
            ]);

        $service = new TestableVaultDepositAddressSyncService($depositService);
        $service->vaultAssetIds = collect(['BTC_TEST']);

        $result = $service->syncVaultDepositAddresses('vault-1', [
            'accountName' => 'client-vault-10',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['summary']['addresses_created']);
        $this->assertSame(1, CryptoAddress::count());
    }

    public function test_vault_asset_added_syncs_all_supported_assets_on_vault(): void
    {
        $depositService = Mockery::mock(DepositAddressService::class);
        $depositService->shouldReceive('listDepositAddressesFromSdk')
            ->once()
            ->with('vault-1', 'BTC_TEST')
            ->andReturn([
                'success' => true,
                'data' => [[
                    'address' => 'btc-address-1',
                    'tag' => '',
                    'customerRefId' => '1',
                ]],
            ]);
        $depositService->shouldReceive('listDepositAddressesFromSdk')
            ->once()
            ->with('vault-1', 'ETH_TEST5')
            ->andReturn([
                'success' => true,
                'data' => [[
                    'address' => 'eth-address-1',
                    'tag' => '',
                    'customerRefId' => '2',
                ]],
            ]);

        $service = new TestableVaultDepositAddressSyncService($depositService);
        $service->vaultAssetIds = collect(['BTC_TEST', 'ETH_TEST5', 'UNSUPPORTED_ASSET']);

        $result = $service->syncFromVaultAssetAdded([
            'accountId' => 'vault-1',
            'assetId' => 'GUSDT_TEST',
            'accountName' => 'client-vault-10',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['summary']['assets_checked']);
        $this->assertSame(2, $result['summary']['assets_synced']);
        $this->assertSame(2, $result['summary']['addresses_created']);
        $this->assertSame(10, $result['summary']['client_id']);
        $this->assertSame(2, CryptoAddress::count());
    }

    public function test_vault_asset_added_skips_unconfigured_assets_on_vault(): void
    {
        $depositService = Mockery::mock(DepositAddressService::class);
        $depositService->shouldNotReceive('listDepositAddressesFromSdk');

        $service = new TestableVaultDepositAddressSyncService($depositService);
        $service->vaultAssetIds = collect(['UNSUPPORTED_ASSET', 'UNKNOWN_TOKEN']);

        $result = $service->syncFromVaultAssetAdded([
            'accountId' => 'vault-1',
            'assetId' => 'UNSUPPORTED_ASSET',
            'accountName' => 'client-vault-10',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['summary']['assets_checked']);
        $this->assertSame(0, $result['summary']['assets_synced']);
        $this->assertSame(0, CryptoAddress::count());
    }

    public function test_transaction_webhook_extracts_vault_from_destination(): void
    {
        $depositService = Mockery::mock(DepositAddressService::class);
        $depositService->shouldReceive('listDepositAddressesFromSdk')
            ->once()
            ->with('vault-1', 'BTC_TEST')
            ->andReturn(['success' => true, 'data' => []]);

        $service = new TestableVaultDepositAddressSyncService($depositService);
        $service->vaultAssetIds = collect(['BTC_TEST']);

        $result = $service->syncFromTransaction([
            'assetId' => 'BTC_TEST',
            'destinationAddress' => 'btc-address-1',
            'destination' => ['id' => 'vault-1', 'type' => 'VAULT_ACCOUNT'],
            'source' => ['id' => 'external', 'type' => 'ONE_TIME_ADDRESS'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('vault-1', $result['summary']['vault_account_id']);
    }

    private function seedFixtures(): void
    {
        DB::table('currencies')->insert([
            ['id' => 1, 'short' => 'BTC', 'is_crypto' => true, 'is_active' => true],
            ['id' => 2, 'short' => 'ETH', 'is_crypto' => true, 'is_active' => true],
        ]);

        DB::table('networks')->insert([
            ['id' => 1, 'currency_id' => 1, 'name' => 'Bitcoin Testnet', 'code' => 'btc', 'is_testnet' => true],
            ['id' => 2, 'currency_id' => 2, 'name' => 'Ethereum Testnet', 'code' => 'eth', 'is_testnet' => true],
        ]);

        DB::table('assets')->insert([
            [
                'id' => 1,
                'currency_id' => 1,
                'network_id' => 1,
                'asset_id' => 'BTC_TEST',
                'is_testnet' => true,
                'type' => 'account',
            ],
            [
                'id' => 2,
                'currency_id' => 2,
                'network_id' => 2,
                'asset_id' => 'ETH_TEST5',
                'is_testnet' => true,
                'type' => 'account',
            ],
        ]);

        Account::create([
            'id' => 1,
            'client_id' => 10,
            'account_currency' => 1,
            'is_active' => true,
        ]);
        Account::create([
            'id' => 2,
            'client_id' => 10,
            'account_currency' => 2,
            'is_active' => true,
        ]);

        FireblocksVaultAccount::create([
            'client_id' => 10,
            'vault_id' => 'vault-1',
            'vault_title' => 'client-vault-10',
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('short')->nullable();
            $table->boolean('is_crypto')->default(false);
            $table->boolean('is_active')->default(true);
        });
        Schema::create('networks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('currency_id');
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_testnet')->default(false);
        });
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('currency_id');
            $table->unsignedBigInteger('network_id');
            $table->string('asset_id');
            $table->boolean('is_testnet')->default(false);
            $table->string('type')->nullable();
        });
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('account_currency');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('fireblocks_vault_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('vault_title')->nullable();
            $table->string('vault_id');
            $table->timestamps();
        });
        Schema::create('crypto_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('provider')->nullable();
            $table->string('asset_id')->nullable();
            $table->string('vault_account_id')->nullable();
            $table->unsignedBigInteger('network_id')->nullable();
            $table->string('crypto_address')->nullable();
            $table->string('legacy_address')->nullable();
            $table->string('destination_tag')->nullable();
            $table->string('network')->nullable();
            $table->string('ged_external_id')->nullable();
            $table->string('address_alias')->nullable();
            $table->timestamps();
        });
    }
}

class TestableVaultDepositAddressSyncService extends VaultDepositAddressSyncService
{
    public Collection $vaultAssetIds;

    protected function fetchVaultAssetIds(string $vaultAccountId): Collection
    {
        return $this->vaultAssetIds ?? collect();
    }
}
