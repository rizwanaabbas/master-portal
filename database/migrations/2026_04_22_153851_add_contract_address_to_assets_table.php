<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContractAddressToAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'contract_address')) {
                $table->string('contract_address')->nullable()->after('type');
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
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'contract_address')) {
                $table->dropColumn('contract_address');
            }
        });
    }
}
