<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFireblocksTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('fireblocks_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('fireblocks_tx_id')->nullable()->index();
            $table->string('asset_id')->index();
            $table->string('source_vault_account_id');
            $table->string('destination_address');
            $table->string('destination_tag')->nullable();
            $table->decimal('amount', 36, 18);
            $table->string('status')->default('PENDING');
            $table->string('tx_hash')->nullable();
            $table->string('fee')->nullable();
            $table->text('note')->nullable();
            $table->string('external_tx_id')->nullable();
            $table->string('customer_ref_id')->nullable();
            $table->text('error_message')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamps();

            $table->index(['status', 'asset_id']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('fireblocks_transactions');
    }
}
