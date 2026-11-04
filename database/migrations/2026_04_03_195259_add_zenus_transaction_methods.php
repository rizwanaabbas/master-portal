<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddZenusTransactionMethods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_methods', function (Blueprint $table) {
            $zenus_module_id = DB::table('modules')
                ->where('name', 'Zenus')
                ->value('id');

            $zenus_methods = ['FED_WIRE', 'ACH', 'CROSS_BORDER_SWIFT'];

            $data = array_map(function ($method) use ($zenus_module_id) {
                return [
                    'name' => $method,
                    'show_on_front' => 0,
                    'module_id' => $zenus_module_id,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $zenus_methods);

            DB::table('transaction_methods')->upsert(
                $data,
                ['name'], // unique column to check
                ['module_id', 'updated_at'] // columns to update if exists
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $zenus_methods = ['FED_WIRE', 'ACH', "INTERNAL_TRANSFER"];
        DB::table('transaction_methods')->whereIn('name', $zenus_methods)->delete();
    }
}
