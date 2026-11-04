<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterClientsAddIsB2bInternalTransferAllowedColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_b2b_internal_transfer_allowed')->after('is_kyc_requested')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Drop columns if they exist
            if (Schema::hasColumn('clients', 'is_b2b_internal_transfer_allowed')) {
                $table->dropColumn('is_b2b_internal_transfer_allowed');
            }
        });
    }
}
