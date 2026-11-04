<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpenpaydTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('openpayd_transactions', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->string('transaction_id', 255)->nullable(); // Unique transaction identifier
            $table->string('parent_transaction_id', 255)->nullable(); 
            
            $table->string('short_id', 50)->nullable(); // Short identifier
            $table->string('account_id')->nullable(); // Account ID
            $table->timestamp('transaction_created_date')->nullable(); // Creation timestamp
            $table->timestamp('transaction_updated_date')->nullable(); // Update timestamp
            $table->string('openpayd_transaction_id')->nullable(); // Transaction identifier
            //$table->string('transaction_id')->nullable(); // Transaction identifier
            $table->string('transaction_category')->nullable(); // Category of transaction
            $table->string('payment_type')->nullable(); // Payment type
            $table->string('type')->nullable(); // Type of transaction
            $table->string('status')->nullable(); // Status of transaction
            $table->string('compliance_status')->nullable(); // Compliance status
            $table->string('account_holder_id')->nullable(); // Account holder ID
            $table->json('response')->nullable(); // Response field as JSON

            $table->timestamps(); // Laravel-managed created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('openpayd_transactions');
    }
}
