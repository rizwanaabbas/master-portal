<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_bank_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->integer('trn_id');
            $table->string('transaction_id')->nullable();
            $table->unsignedBigInteger('parent_transaction_id')->nullable();
            $table->string('short_id', 50)->nullable();

            $table->string('account_id')->nullable();

            $table->timestamp('transaction_created_date')->nullable();
            $table->timestamp('transaction_updated_date')->nullable();

            // 🔁 RENAMED FIELD
            $table->string('virtual_bank_transaction_id')->nullable();

            $table->string('transaction_category')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->string('compliance_status')->nullable();

            $table->string('account_holder_id')->nullable();

            $table->json('response')->nullable();

            $table->timestamps();
        });

        // ✅ Explicit column mapping (SAFE)
        DB::statement("
            INSERT INTO virtual_bank_transactions (
                id,
                trn_id,
                transaction_id,
                parent_transaction_id,
                short_id,
                account_id,
                transaction_created_date,
                transaction_updated_date,
                virtual_bank_transaction_id,
                transaction_category,
                payment_type,
                type,
                status,
                compliance_status,
                account_holder_id,
                response,
                created_at,
                updated_at
            )
            SELECT
                id,
                trn_id,
                transaction_id,
                parent_transaction_id,
                short_id,
                account_id,
                transaction_created_date,
                transaction_updated_date,
                openpayd_transaction_id, -- 🔁 mapped correctly
                transaction_category,
                payment_type,
                type,
                status,
                compliance_status,
                account_holder_id,
                response,
                created_at,
                updated_at
            FROM openpayd_transactions
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_bank_transactions');
    }
};
