<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCryptoBeneficiariesForExternalWallets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crypto_beneficiaries', function (Blueprint $table) {
            // Link to the external wallet container
            $table->unsignedBigInteger('external_wallet_id')->nullable()->after('id');

            // Asset type distinguishes crypto vs fiat rails
            $table->enum('asset_type', [
                'crypto', 'iban', 'aba', 'sepa', 'ach', 'us_wire',
                'spei', 'pix', 'momo', 'lbt', 'interac', 'payid', 'chaps',
            ])->default('crypto')->after('provider');

            // Stores the full additionalInfo object for fiat rails
            $table->json('additional_info')->nullable()->after('asset_type');

            // Remove columns moved to crypto_external_wallets
            $table->dropColumn(['fireblocks_wallet_id', 'fireblocks_customer_ref_id']);
        });
    }

    public function down()
    {
        Schema::table('crypto_beneficiaries', function (Blueprint $table) {
            $table->dropColumn(['external_wallet_id', 'asset_type', 'additional_info']);
            $table->string('fireblocks_wallet_id')->nullable();
            $table->string('fireblocks_customer_ref_id')->nullable();
        });
    }
}
