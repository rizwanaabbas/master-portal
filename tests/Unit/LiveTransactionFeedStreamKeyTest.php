<?php

namespace Tests\Unit;

use App\Modules\LiveTransactions\Models\LiveTransactionFeed;
use App\Modules\LiveTransactions\Services\LiveTransactionFeedService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class LiveTransactionFeedStreamKeyTest extends TestCase
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

        Schema::create('live_transaction_feed', function (Blueprint $table) {
            $table->id();
            $table->string('stream_key', 120)->unique();
            $table->unsignedBigInteger('tr_request_id')->nullable();
            $table->unsignedBigInteger('account_transaction_id')->nullable();
            $table->string('fireblocks_tx_id', 64)->nullable();
            $table->unsignedBigInteger('liquidity_order_id')->nullable();
            $table->string('portal_source', 80)->nullable();
            $table->string('flow_from', 255)->nullable();
            $table->string('flow_to', 255)->nullable();
            $table->string('transaction_nature', 60)->nullable();
            $table->string('transaction_type', 30)->nullable();
            $table->string('asset_code', 40)->nullable();
            $table->string('asset_name', 120)->nullable();
            $table->string('network_name', 120)->nullable();
            $table->string('amount_display', 80)->nullable();
            $table->string('fiat_display', 80)->nullable();
            $table->string('status_label', 80)->nullable();
            $table->string('status_tone', 20)->default('pending');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('ledger_url', 500)->nullable();
            $table->string('initiator_name', 120)->nullable();
            $table->string('client_name', 160)->nullable();
            $table->string('account_label', 160)->nullable();
            $table->boolean('is_crypto')->default(false);
            $table->string('direction', 20)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        config(['database.default' => $this->originalDatabase]);

        parent::tearDown();
    }

    public function test_upsert_removes_superseded_fireblocks_stream_key(): void
    {
        LiveTransactionFeed::create([
            'stream_key' => 'fb:tx-abc',
            'fireblocks_tx_id' => 'tx-abc',
            'status_label' => 'Submitted',
            'status_tone' => 'pending',
            'progress_percent' => 10,
            'occurred_at' => now()->subMinute(),
        ]);

        $service = app(LiveTransactionFeedService::class);
        $upsert = new ReflectionMethod($service, 'upsert');
        $upsert->setAccessible(true);

        $upsert->invoke($service, [
            'stream_key' => 'tr:42',
            'tr_request_id' => 42,
            'fireblocks_tx_id' => 'tx-abc',
            'status_label' => 'Pending',
            'status_tone' => 'pending',
            'progress_percent' => 20,
            'occurred_at' => now(),
            'is_crypto' => true,
            'direction' => 'outgoing',
        ]);

        $this->assertSame(1, LiveTransactionFeed::count());
        $this->assertNotNull(LiveTransactionFeed::where('stream_key', 'tr:42')->first());
        $this->assertNull(LiveTransactionFeed::where('stream_key', 'fb:tx-abc')->first());
    }

    public function test_resolve_feed_cursor_uses_latest_item_timestamp(): void
    {
        $service = app(LiveTransactionFeedService::class);
        $method = new ReflectionMethod($service, 'resolveFeedCursor');
        $method->setAccessible(true);

        $cursor = $method->invoke($service, null, [
            ['updated_at' => '2026-08-25T10:00:00+00:00'],
            ['updated_at' => '2026-08-25T10:05:00+00:00'],
        ]);

        $this->assertSame('2026-08-25T10:05:00+00:00', $cursor);

        $unchanged = $method->invoke($service, \Carbon\Carbon::parse('2026-08-25T09:00:00+00:00'), []);
        $this->assertSame('2026-08-25T09:00:00+00:00', $unchanged);
    }
}
