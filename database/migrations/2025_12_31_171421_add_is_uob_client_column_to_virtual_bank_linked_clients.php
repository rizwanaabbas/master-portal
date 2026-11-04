<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsUobClientColumnToVirtualBankLinkedClients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('virtual_bank_linked_clients', function (Blueprint $table) {
            // Add is_uob_client column
            $table->boolean('is_uob_client')->default(false)->after('country');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('virtual_bank_linked_clients', function (Blueprint $table) {
            // Remove the is_uob_client column
            $table->dropColumn('is_uob_client');
        });
    }
}
