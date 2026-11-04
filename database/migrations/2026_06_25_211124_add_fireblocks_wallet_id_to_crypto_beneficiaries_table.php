<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFireblocksWalletIdToCryptoBeneficiariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crypto_beneficiaries', function (Blueprint $table) {
            $table->string('fireblocks_wallet_id')->nullable()->after('external_wallet_id');
        });
    }

    public function down()
    {
        Schema::table('crypto_beneficiaries', function (Blueprint $table) {
            $table->dropColumn('fireblocks_wallet_id');
        });
    }
}
