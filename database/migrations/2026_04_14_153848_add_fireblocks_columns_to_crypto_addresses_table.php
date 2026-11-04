<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFireblocksColumnsToCryptoAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crypto_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('crypto_addresses', 'vault_account_id')) {
                $table->string('vault_account_id')->nullable()->index()->after('account_id');
            }
            if (!Schema::hasColumn('crypto_addresses', 'asset_id')) {
                $table->string('asset_id')->nullable()->index()->after('vault_account_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crypto_addresses', function (Blueprint $table) {
            if (Schema::hasColumn('crypto_addresses', 'vault_account_id')) {
                $table->dropColumn('vault_account_id');
            }
            if (Schema::hasColumn('crypto_addresses', 'asset_id')) {
                $table->dropColumn('asset_id');
            }
        });
    }
}
