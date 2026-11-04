<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpenpaydLinkedClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('openpayd_linked_clients', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID
            $table->string('account_holder_id')->nullable(); // Nullable string for account holder ID
            $table->string('status')->default('INACTIVE'); // Default value 'INACTIVE'
            $table->string('country')->nullable(); // Nullable string for country
            $table->string('kyc_status')->nullable(); // Nullable string for KYC status
            $table->text('response_data')->nullable(); // Nullable text for response data
            $table->timestamps(); // Created and updated timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('openpayd_linked_clients');
    }
}
