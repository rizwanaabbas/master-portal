<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCryptoAddressComplianceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crypto_address_compliance', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('crypto_beneficiary_id')->nullable()->index();
            $table->string('wallet_address');
            $table->enum('entity_type', ['deposit_address', 'withdraw_address']);
            $table->string('risk')->nullable();              // Low / Medium / High / Severe
            $table->string('status')->nullable();            // COMPLETE / PENDING etc.
            $table->string('address_type')->nullable();      // PRIVATE_WALLET etc.
            $table->string('cluster_name')->nullable();
            $table->string('cluster_category')->nullable();
            $table->text('risk_reason')->nullable();
            $table->json('complete_info')->nullable();       // full Chainalysis response
            $table->timestamps();

            $table->foreign('crypto_beneficiary_id')
                  ->references('id')->on('crypto_beneficiaries')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crypto_address_compliance');
    }
}
