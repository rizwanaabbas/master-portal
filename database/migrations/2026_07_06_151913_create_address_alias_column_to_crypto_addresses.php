<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressAliasColumnToCryptoAddresses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crypto_addresses', function (Blueprint $table) {
            Schema::hasColumn('crypto_addresses', 'address_alias') ?: $table->string('address_alias')->nullable()->after('address');
            Schema::hasColumn('crypto_addresses', 'ged_external_id') ?: $table->string('ged_external_id')->nullable()->after('account_id');
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
            Schema::hasColumn('crypto_addresses', 'address_alias') && $table->dropColumn('address_alias');
            Schema::hasColumn('crypto_addresses', 'ged_external_id') && $table->dropColumn('ged_external_id');
        });
    }
}
