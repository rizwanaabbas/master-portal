<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLegacyAddressToCryptoAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crypto_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('crypto_addresses', 'legacy_address')) {
                $table->string('legacy_address')->nullable()->after('crypto_address');
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
            if (Schema::hasColumn('crypto_addresses', 'legacy_address')) {
                $table->dropColumn('legacy_address');
            }
        });
    }
}
