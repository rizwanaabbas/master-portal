<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\CryptoAddress;
use App\Models\FireblocksTransaction;
use App\Models\TRRequest;
use App\Modules\Fireblocks\Services\WebhookService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FireblocksDepositWebhookTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Model::setEventDispatcher($this->eventDispatcher);
        DB::purge('sqlite');
        config(['database.default' => $this->originalDatabase]);

        parent::tearDown();
    }

    public function test_confirmed_incoming_deposit_creates_net_credit_once(): void
    {
        $this->createDepositAccount();
        $service = new DepositWebhookService();

        $result = $service->handleEvent($this->payload());
        $replay = $service->handleEvent($this->payload());

        $this->assertTrue($result['success']);
        $this->assertTrue($replay['success']);
        $this->assertSame(1, TRRequest::count());
        $this->assertSame(1, AccountTransaction::count());
        $this->assertSame(1, FireblocksTransaction::count());
        $this->assertSame(9.5, (float) AccountTransaction::first()->amount);
        $this->assertSame('Approved', AccountTransaction::first()->status);
        $this->assertSame(AccountTransaction::first()->id, TRRequest::first()->link_tr_id);
        $this->assertSame(1, $service->feeCalls);
        $this->assertSame(1, $service->notificationCalls);
    }

    public function test_pending_deposit_does_not_credit_account(): void
    {
        $this->createDepositAccount();
        $service = new DepositWebhookService();
        $payload = $this->payload([
            'status' => 'CONFIRMING',
            'subStatus' => 'PENDING_BLOCKCHAIN_CONFIRMATIONS',
        ]);

        $result = $service->handleEvent($payload);

        $this->assertTrue($result['success']);
        $this->assertSame(0, AccountTransaction::count());
        $this->assertSame(0, TRRequest::count());
    }

    public function test_vault_to_vault_transfer_is_not_treated_as_deposit(): void
    {
        $this->createDepositAccount();
        $service = new DepositWebhookService();
        $payload = $this->payload([
            'source' => ['id' => 'another-vault', 'type' => 'VAULT_ACCOUNT'],
        ]);

        $service->handleEvent($payload);

        $this->assertSame(0, AccountTransaction::count());
        $this->assertSame(0, $service->feeCalls);
    }

    public function test_tron_station_gas_feed_never_credits_client_ledger(): void
    {
        $this->createDepositAccount();
        $service = new DepositWebhookService();
        $payload = $this->payload([
            'assetId' => 'TRX_TEST',
            'amount' => 50,
            'externalTxId' => 'cib-sweep-gas-99-20260908120000',
            'note' => 'Gas feed (TRX_TEST) for sweep job #99',
            'source' => ['id' => 'tron-station', 'type' => 'VAULT_ACCOUNT'],
        ]);

        $result = $service->handleEvent($payload);

        $this->assertTrue($result['success']);
        $this->assertSame(0, AccountTransaction::count());
        $this->assertSame(0, TRRequest::count());
        $this->assertSame(0, $service->feeCalls);
        $this->assertSame(1, FireblocksTransaction::count());
        $this->assertSame(
            FireblocksTransaction::CUSTODY_ONLY_SWEEP_GAS_FEED_NOTE,
            FireblocksTransaction::first()->note
        );
    }

    public function test_ambiguous_address_match_fails_closed(): void
    {
        $this->createDepositAccount();
        CryptoAddress::create([
            'account_id' => 1,
            'provider' => 'fireblocks',
            'asset_id' => 'BTC_TEST',
            'vault_account_id' => 'vault-1',
            'crypto_address' => 'deposit-address',
            'network' => 'Bitcoin Testnet',
        ]);
        $service = new DepositWebhookService();

        $result = $service->handleEvent($this->payload());

        $this->assertFalse($result['success']);
        $this->assertSame(0, AccountTransaction::count());
        $this->assertSame(0, FireblocksTransaction::count());
    }

    public function test_tag_must_match_registered_deposit_address(): void
    {
        $this->createDepositAccount('12345');
        $service = new DepositWebhookService();

        $result = $service->handleEvent($this->payload(['destinationTag' => '99999']));

        $this->assertFalse($result['success']);
        $this->assertSame(0, AccountTransaction::count());
    }

    public function test_inactive_account_is_not_credited(): void
    {
        $this->createDepositAccount('', false);
        $service = new DepositWebhookService();

        $result = $service->handleEvent($this->payload());

        $this->assertFalse($result['success']);
        $this->assertSame(0, AccountTransaction::count());
    }

    public function test_confirmed_incoming_deposit_credits_when_monitoring_bootstrap_exists(): void
    {
        $this->createDepositAccount();
        FireblocksTransaction::create([
            'fireblocks_tx_id' => 'fireblocks-deposit-1',
            'asset_id' => 'BTC_TEST',
            'source_vault_account_id' => 'external-source',
            'destination_address' => 'deposit-address',
            'amount' => 10,
            'status' => 'CONFIRMING',
        ]);

        $service = new DepositWebhookService();
        $result = $service->handleEvent($this->payload());

        $this->assertTrue($result['success']);
        $this->assertSame(1, FireblocksTransaction::count());
        $this->assertSame(1, AccountTransaction::count());
        $this->assertSame('Incoming client deposit', FireblocksTransaction::first()->note);
        $this->assertSame('COMPLETED', FireblocksTransaction::first()->status);
    }

    public function test_existing_withdrawal_status_is_saved_without_tr_request(): void
    {
        $this->createDepositAccount();
        FireblocksTransaction::create([
            'fireblocks_tx_id' => 'withdrawal-1',
            'asset_id' => 'BTC_TEST',
            'source_vault_account_id' => 'vault-1',
            'destination_address' => 'external-address',
            'amount' => 2,
            'status' => 'PENDING',
        ]);
        $transaction = new AccountTransaction();
        $transaction->account_id = 1;
        $transaction->currency_id = 1;
        $transaction->transaction_type = 'Debit';
        $transaction->transaction_nature = 'Withdraw';
        $transaction->transaction_date = now();
        $transaction->amount = 2;
        $transaction->status = 'Pending';
        $transaction->crypto_system = 'fireblocks';
        $transaction->crypto_transaction_id = 'withdrawal-1';
        $transaction->save();

        $service = new DepositWebhookService();
        $payload = $this->payload([
            'id' => 'withdrawal-1',
            'source' => ['id' => 'vault-1', 'type' => 'VAULT_ACCOUNT'],
            'destination' => ['id' => '', 'type' => 'ONE_TIME_ADDRESS'],
            'destinationAddress' => 'external-address',
            'amountInfo' => ['amount' => '2'],
            'amount' => 2,
        ]);

        $service->handleEvent($payload);

        $this->assertSame('Approved', $transaction->fresh()->status);
        $this->assertSame(0, $transaction->fresh()->approved_by);
        $this->assertNotNull($transaction->fresh()->approved_at);
    }

    private function createDepositAccount(string $tag = '', bool $active = true): void
    {
        DB::table('currencies')->insert([
            'id' => 1,
            'short' => 'BTC',
            'is_crypto' => true,
        ]);
        Account::create([
            'id' => 1,
            'client_id' => 10,
            'account_currency' => 1,
            'is_active' => $active,
        ]);
        DB::table('client_users')->insert(['client_id' => 10, 'user_id' => 20]);
        CryptoAddress::create([
            'account_id' => 1,
            'provider' => 'fireblocks',
            'asset_id' => 'BTC_TEST',
            'vault_account_id' => 'vault-1',
            'crypto_address' => 'deposit-address',
            'destination_tag' => $tag,
            'network' => 'Bitcoin Testnet',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return [
            'eventType' => 'transaction.status.updated',
            'data' => array_replace_recursive([
                'id' => 'fireblocks-deposit-1',
                'status' => 'COMPLETED',
                'subStatus' => 'CONFIRMED',
                'assetId' => 'BTC_TEST',
                'source' => ['id' => 'external-source', 'type' => 'ONE_TIME_ADDRESS'],
                'destination' => ['id' => 'vault-1', 'type' => 'VAULT_ACCOUNT'],
                'destinationAddress' => 'deposit-address',
                'destinationTag' => '',
                'amount' => 10,
                'amountInfo' => ['amount' => '10'],
                'txHash' => 'tx-hash',
            ], $overrides),
        ];
    }

    private function createSchema(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_enabled')->default(false);
        });
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('short')->nullable();
            $table->boolean('is_crypto')->default(false);
        });
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('account_currency');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('client_users', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('user_id');
        });
        Schema::create('crypto_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('provider')->nullable();
            $table->string('asset_id')->nullable();
            $table->string('vault_account_id')->nullable();
            $table->string('crypto_address')->nullable();
            $table->string('legacy_address')->nullable();
            $table->string('destination_tag')->nullable();
            $table->string('network')->nullable();
            $table->timestamps();
        });
        Schema::create('fireblocks_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('fireblocks_tx_id')->nullable()->unique();
            $table->string('asset_id');
            $table->string('source_vault_account_id');
            $table->string('destination_address');
            $table->string('destination_tag')->nullable();
            $table->decimal('amount', 36, 18);
            $table->string('status');
            $table->string('tx_hash')->nullable();
            $table->string('fee')->nullable();
            $table->text('note')->nullable();
            $table->string('external_tx_id')->nullable();
            $table->string('customer_ref_id')->nullable();
            $table->text('error_message')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamps();
        });
        Schema::create('account_transaction_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('currency_id');
            $table->string('transaction_type');
            $table->string('transaction_nature');
            $table->dateTime('transaction_date');
            $table->decimal('amount', 36, 18);
            $table->decimal('crypto_amount', 36, 18)->nullable();
            $table->string('remarks')->nullable();
            $table->string('status');
            $table->string('crypto_system')->nullable();
            $table->string('crypto_transaction_id')->nullable();
            $table->string('to_wallet_address')->nullable();
            $table->string('destination_tag')->nullable();
            $table->string('crypto_network')->nullable();
            $table->unsignedBigInteger('link_tr_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('tr_request_id')->nullable();
            $table->unsignedBigInteger('currency_id');
            $table->string('transaction_type');
            $table->string('transaction_nature');
            $table->dateTime('transaction_date');
            $table->decimal('amount', 36, 18);
            $table->boolean('is_crypto')->default(false);
            $table->string('remarks')->nullable();
            $table->string('status');
            $table->string('crypto_system')->nullable();
            $table->string('crypto_transaction_id')->nullable();
            $table->unsignedBigInteger('crypto_details_id')->nullable();
            $table->string('to_wallet_address')->nullable();
            $table->string('crypto_network')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });
        Schema::create('crypto_transactions_log', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id');
            $table->json('details')->nullable();
            $table->string('crypto_system');
            $table->string('source');
            $table->timestamps();
        });
    }
}

class DepositWebhookService extends WebhookService
{
    public int $feeCalls = 0;
    public int $notificationCalls = 0;

    protected function applyDepositFees(
        Account $account,
        float $amount,
        string $fireblocksTxId,
        CryptoAddress $depositAddress,
        array $data
    ): array {
        $this->feeCalls++;

        return [
            'applied' => true,
            'total_fee' => 0.5,
            'net_amount' => $amount - 0.5,
            'charge_ids' => [],
        ];
    }

    protected function sendDepositNotifications(TRRequest $trRequest, AccountTransaction $credit): void
    {
        $this->notificationCalls++;
    }
}
