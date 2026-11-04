<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddModuleIdForSepaAndSepaInstantInTransactionMethods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_methods', function (Blueprint $table) {
            // get the OpenPayd module_id from module table
            $openpayd_module_id = DB::table('modules')
                ->where('name', 'OpenPayd')
                ->value('id');
            
            // Update the module_id for SEPA and SEPA_INSTANT transaction methods
            DB::table('transaction_methods')
                ->whereIn('name', ['SEPA', 'SEPA_INSTANT'])
                ->update(['module_id' => $openpayd_module_id]);
            
            // Make the SWIFT transaction method default
            DB::table('transaction_methods')
                ->where('name', 'SWIFT')
                ->update(['is_default' => true]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_methods', function (Blueprint $table) {
            // make module_id nullable for SEPA and SEPA_INSTANT
            DB::table('transaction_methods')
                ->whereIn('name', ['SEPA', 'SEPA_INSTANT'])
                ->update(['module_id' => null]);
        });
    }
}
