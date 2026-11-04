<?php

namespace Tests\Unit;

use App\Helpers\Common;
use App\Models\AccountTransaction;
use App\Models\TRRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommonAvailableBalanceTest extends TestCase
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

        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->string('transaction_type');
            $table->string('transaction_nature')->nullable();
            $table->decimal('amount', 18, 8)->default(0);
            $table->string('status');
            $table->unsignedBigInteger('tr_request_id')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });

        Schema::create('account_transaction_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->string('transaction_type');
            $table->string('transaction_nature')->nullable();
            $table->decimal('amount', 18, 8)->default(0);
            $table->decimal('crypto_amount', 18, 8)->nullable();
            $table->string('status');
            $table->boolean('otp_verified')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });

        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trn_id');
            $table->decimal('amount', 18, 8)->nullable();
            $table->decimal('trn_fee', 18, 8)->default(0);
            $table->unsignedBigInteger('trn_fee_client_account_id')->default(0);
            $table->decimal('service_fee', 18, 8)->default(0);
            $table->unsignedBigInteger('service_fee_client_account_id')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Model::setEventDispatcher($this->eventDispatcher);
        DB::purge('sqlite');
        config(['database.default' => $this->originalDatabase]);

        parent::tearDown();
    }

    public function test_available_equals_ledger_when_no_pending_holds(): void
    {
        $this->seedLedger(1, 200.0, 50.0);

        $this->assertSame(150.0, (float) Common::getApprovedLedgerBalanceByAccountId(1));
        $this->assertSame(150.0, (float) Common::getAccountBalanceById(1));
        $this->assertSame(0.0, Common::getPendingDebitHoldByAccountId(1));
    }

    public function test_pending_fiat_debit_does_not_hold_without_crypto(): void
    {
        $this->seedLedger(1, 200.0, 0.0);

        AccountTransaction::unguard();
        AccountTransaction::create([
            'account_id' => 1,
            'currency_id' => 1,
            'transaction_type' => 'Debit',
            'transaction_nature' => 'TT',
            'amount' => 200,
            'status' => 'Pending',
            'is_deleted' => false,
        ]);
        AccountTransaction::reguard();

        $this->assertSame(200.0, (float) Common::getApprovedLedgerBalanceByAccountId(1));
        $this->assertSame(200.0, (float) Common::getAccountBalanceById(1));
        $this->assertSame(0.0, Common::getPendingDebitHoldByAccountId(1));
        $this->assertFalse(Common::accountHasPendingCryptoDebitHold(1));
    }

    public function test_tr_only_withdraw_hold_without_double_counting_at(): void
    {
        $this->seedLedger(1, 200.0, 0.0);

        TRRequest::unguard();
        $tr = TRRequest::create([
            'account_id' => 1,
            'currency_id' => 1,
            'transaction_type' => 'Debit',
            'transaction_nature' => 'Withdraw',
            'amount' => 0,
            'crypto_amount' => 75,
            'status' => 'Pending',
            'otp_verified' => true,
            'is_deleted' => false,
        ]);
        TRRequest::reguard();

        AccountTransaction::unguard();
        AccountTransaction::create([
            'account_id' => 1,
            'currency_id' => 1,
            'transaction_type' => 'Debit',
            'transaction_nature' => 'Withdraw',
            'amount' => 75,
            'status' => 'Pending',
            'tr_request_id' => $tr->id,
            'is_deleted' => false,
        ]);
        AccountTransaction::reguard();

        $this->assertSame(75.0, Common::getPendingDebitHoldByAccountId(1));
        $this->assertSame(125.0, (float) Common::getAccountBalanceById(1));
    }

    public function test_tr_approval_available_includes_this_requests_pending_hold(): void
    {
        $this->seedLedger(1, 600.0, 0.0);

        TRRequest::unguard();
        $tr = TRRequest::create([
            'account_id' => 1,
            'currency_id' => 1,
            'transaction_type' => 'Debit',
            'transaction_nature' => 'Withdraw',
            'amount' => 500,
            'status' => 'Pending',
            'otp_verified' => true,
            'is_deleted' => false,
        ]);
        TRRequest::reguard();

        $this->assertSame(100.0, (float) Common::getAccountBalanceById(1));
        $this->assertSame(500.0, Common::pendingHoldAttributableToTrRequest($tr));
        $this->assertSame(600.0, Common::getAvailableBalanceForTrApproval($tr));
        $this->assertTrue(Common::trApprovalHasSufficientBalance($tr, 500.0));

        $breakdown = Common::getAccountBalanceBreakdownForTrApproval($tr);
        $this->assertSame(600.0, (float) $breakdown['ledger']);
        $this->assertSame(500.0, (float) $breakdown['this_request_hold']);
        $this->assertSame(600.0, (float) $breakdown['approval_available']);
        $this->assertTrue($breakdown['crypto_hold_active']);
    }

    public function test_pending_fiat_tts_do_not_reduce_approval_max_without_crypto(): void
    {
        $this->seedLedger(1, 60000.0, 0.0);

        TRRequest::unguard();
        TRRequest::create([
            'account_id' => 1,
            'currency_id' => 1,
            'transaction_type' => 'Debit',
            'transaction_nature' => 'TT',
            'amount' => 5000,
            'status' => 'Pending',
            'otp_verified' => true,
            'is_deleted' => false,
        ]);
        $tt = TRRequest::create([
            'account_id' => 1,
            'currency_id' => 1,
            'transaction_type' => 'Debit',
            'transaction_nature' => 'TT',
            'amount' => 49500,
            'status' => 'Pending',
            'otp_verified' => true,
            'is_deleted' => false,
        ]);
        TRRequest::create([
            'account_id' => 1,
            'currency_id' => 1,
            'transaction_type' => 'Debit',
            'transaction_nature' => 'TT',
            'amount' => 1001,
            'status' => 'Pending',
            'otp_verified' => false,
            'is_deleted' => false,
        ]);
        TRRequest::reguard();

        $this->assertFalse(Common::accountHasPendingCryptoDebitHold(1));
        $this->assertSame(0.0, Common::getPendingDebitHoldByAccountId(1));
        $this->assertSame(60000.0, (float) Common::getAccountBalanceById(1));
        $this->assertSame(60000.0, Common::getAvailableBalanceForTrApproval($tt));
        $this->assertTrue(Common::trApprovalHasSufficientBalance($tt, 49500.0));

        $breakdown = Common::getAccountBalanceBreakdownForTrApproval($tt);
        $this->assertSame(60000.0, (float) $breakdown['approval_available']);
        $this->assertFalse($breakdown['crypto_hold_active']);
    }

    public function test_pending_crypto_causes_fiat_tts_to_hold(): void
    {
        $this->seedLedger(1, 60000.0, 0.0);

        TRRequest::unguard();
        TRRequest::create([
            'account_id' => 1,
            'currency_id' => 1,
            'transaction_type' => 'Debit',
            'transaction_nature' => 'Withdraw',
            'amount' => 0,
            'crypto_amount' => 10000,
            'status' => 'Pending',
            'otp_verified' => true,
            'is_deleted' => false,
        ]);
        $tt = TRRequest::create([
            'account_id' => 1,
            'currency_id' => 1,
            'transaction_type' => 'Debit',
            'transaction_nature' => 'TT',
            'amount' => 49500,
            'status' => 'Pending',
            'otp_verified' => true,
            'is_deleted' => false,
        ]);
        TRRequest::create([
            'account_id' => 1,
            'currency_id' => 1,
            'transaction_type' => 'Debit',
            'transaction_nature' => 'TT',
            'amount' => 1001,
            'status' => 'Pending',
            'otp_verified' => false,
            'is_deleted' => false,
        ]);
        TRRequest::reguard();

        $this->assertTrue(Common::accountHasPendingCryptoDebitHold(1));
        $this->assertSame(59500.0, Common::getPendingDebitHoldByAccountId(1));
        $this->assertSame(500.0, (float) Common::getAccountBalanceById(1));
        $this->assertSame(50000.0, Common::getAvailableBalanceForTrApproval($tt));
        $this->assertTrue(Common::trApprovalHasSufficientBalance($tt, 49500.0));
        $this->assertFalse(Common::trApprovalHasSufficientBalance($tt, 49500.0, 600.0, null, 1, null));

        $message = Common::trApprovalInsufficientBalanceMessage($tt, 49500.0, 600.0, null, 1, null);
        $this->assertStringContainsString('Insufficient balance for this approval', $message);
        $this->assertStringContainsString('pending crypto', $message);
    }

    public function test_unverified_otp_crypto_does_not_activate_holds(): void
    {
        $this->seedLedger(1, 200.0, 0.0);

        TRRequest::unguard();
        TRRequest::create([
            'account_id' => 1,
            'currency_id' => 1,
            'transaction_type' => 'Debit',
            'transaction_nature' => 'Withdraw',
            'crypto_amount' => 75,
            'status' => 'Pending',
            'otp_verified' => false,
            'is_deleted' => false,
        ]);
        TRRequest::reguard();

        $this->assertFalse(Common::accountHasPendingCryptoDebitHold(1));
        $this->assertSame(0.0, Common::getPendingDebitHoldByAccountId(1));
        $this->assertSame(200.0, (float) Common::getAccountBalanceById(1));
    }

    public function test_rejected_pending_debit_is_not_counted_as_hold(): void
    {
        $this->seedLedger(1, 100.0, 0.0);

        AccountTransaction::unguard();
        AccountTransaction::create([
            'account_id' => 1,
            'currency_id' => 1,
            'transaction_type' => 'Debit',
            'amount' => 40,
            'status' => 'Rejected',
            'is_deleted' => false,
        ]);
        AccountTransaction::reguard();

        $this->assertSame(100.0, (float) Common::getAccountBalanceById(1));
    }

    private function seedLedger(int $accountId, float $credit, float $debit): void
    {
        AccountTransaction::unguard();
        if ($credit > 0) {
            AccountTransaction::create([
                'account_id' => $accountId,
                'currency_id' => 1,
                'transaction_type' => 'Credit',
                'amount' => $credit,
                'status' => 'Approved',
                'is_deleted' => false,
            ]);
        }
        if ($debit > 0) {
            AccountTransaction::create([
                'account_id' => $accountId,
                'currency_id' => 1,
                'transaction_type' => 'Debit',
                'amount' => $debit,
                'status' => 'Approved',
                'is_deleted' => false,
            ]);
        }
        AccountTransaction::reguard();
    }
}
