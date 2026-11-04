<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFireblocksFieldsToCryptoBeneficiariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crypto_beneficiaries', function (Blueprint $table) {
            $table->string('fireblocks_wallet_id')->nullable()->after('provider');
            $table->string('fireblocks_asset_id')->nullable()->after('fireblocks_wallet_id');
            $table->string('fireblocks_asset_status')->nullable()->after('fireblocks_asset_id');
            $table->timestamp('fireblocks_activation_time')->nullable()->after('fireblocks_asset_status');
            $table->string('fireblocks_customer_ref_id')->nullable()->after('fireblocks_activation_time');
        });
    }

    public function down()
    {
        Schema::table('crypto_beneficiaries', function (Blueprint $table) {
            $table->dropColumn([
                'fireblocks_wallet_id',
                'fireblocks_asset_id',
                'fireblocks_asset_status',
                'fireblocks_activation_time',
                'fireblocks_customer_ref_id',
            ]);
        });
    }
}
