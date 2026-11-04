<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {
        /*
        |----------------------------------------------------------
        | Create virtual_bank_accounts table
        |----------------------------------------------------------
        */
        Schema::create('virtual_bank_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('account_id');
            $table->unsignedBigInteger('client_id')->nullable();

            $table->string('virtual_bank_account_id')->nullable();
            $table->unsignedBigInteger('linked_client_id')->nullable();

            $table->string('currency');
            $table->unsignedBigInteger('currency_id')->nullable();

            $table->string('status');
            $table->string('internal_account_id');

            $table->string('bank_country');
            $table->text('bank_address');

            $table->string('iban');
            $table->string('bic');
            $table->string('account_number');

            $table->string('bank_name');
            $table->string('bank_account_holder_name');

            $table->string('vendor_name'); // openpayd, solaris, etc.

            $table->string('payment_type')->nullable();
            $table->string('domestic')->nullable();

            $table->json('routing_code_entries')->nullable();
            $table->string('pay_in_reference')->nullable();

            $table->timestamps();
        });

        /*
        |----------------------------------------------------------
        | Migrate data from openpayd_bank_account
        |----------------------------------------------------------
        */
        if (Schema::hasTable('openpayd_bank_account')) {

            DB::statement("
                INSERT INTO virtual_bank_accounts (
                    id,
                    account_id,
                    client_id,
                    virtual_bank_account_id,
                    linked_client_id,
                    currency,
                    currency_id,
                    status,
                    internal_account_id,
                    bank_country,
                    bank_address,
                    iban,
                    bic,
                    account_number,
                    bank_name,
                    bank_account_holder_name,
                    vendor_name,
                    payment_type,
                    domestic,
                    routing_code_entries,
                    pay_in_reference,
                    created_at,
                    updated_at
                )
                SELECT
                    id,
                    account_id,
                    client_id,
                    openpayd_account_id,
                    openpayd_linked_client_id,
                    currency,
                    currency_id,
                    status,
                    internal_account_id,
                    bank_country,
                    bank_address,
                    iban,
                    bic,
                    account_number,
                    bank_name,
                    bank_account_holder_name,
                    provider,
                    payment_type,
                    domestic,
                    routing_code_entries,
                    pay_in_reference,
                    created_at,
                    updated_at
                FROM openpayd_bank_account
            ");
        }
    }

    public function down(): void
    {
        /*
        |----------------------------------------------------------
        | Drop virtual_bank_accounts
        |----------------------------------------------------------
        */
        Schema::dropIfExists('virtual_bank_accounts');
    }
};

