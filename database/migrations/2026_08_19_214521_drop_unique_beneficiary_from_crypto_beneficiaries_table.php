<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUniqueBeneficiaryFromCryptoBeneficiariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crypto_beneficiaries', function (Blueprint $table) {
            $table->dropUnique('unique_beneficiary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crypto_beneficiaries', function (Blueprint $table) {
            $table->unique(['account_id', 'wallet_address', 'network_id'], 'unique_beneficiary');
        });
    }
}
