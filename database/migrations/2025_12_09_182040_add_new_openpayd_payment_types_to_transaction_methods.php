<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddNewOpenpaydPaymentTypesToTransactionMethods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_methods', function (Blueprint $table) {
            $openpayd_module_id = DB::table('modules')
                ->where('name', 'OpenPayd')
                ->value('id');
            
            $openpayd_methods = ['TARGET2', 'CHAPS', 'FASTER_PAYMENTS'];

            // Get existing methods from DB
            $existingMethods = DB::table('transaction_methods')
                ->whereIn('name', $openpayd_methods)
                ->pluck('name')
                ->toArray();

            // Filter out the ones that already exist
            $methodsToInsert = array_diff($openpayd_methods, $existingMethods);

            $insertData = array_map(function($method) use ($openpayd_module_id) {
                return [
                    'name' => $method,
                    'show_on_front' => 0,
                    'module_id' => $openpayd_module_id,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $methodsToInsert);

            // Insert only if there are new methods
            if (!empty($insertData)) {
                DB::table('transaction_methods')->insert($insertData);
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
        $openpayd_methods = ['TARGET2', 'CHAPS', 'FASTER_PAYMENTS'];

        DB::table('transaction_methods')
            ->whereIn('name', $openpayd_methods)
            ->delete();
    }

}
