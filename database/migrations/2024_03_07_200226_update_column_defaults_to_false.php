<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnDefaultsToFalse extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_bank')->default(0)->change();
            $table->integer('parent_id')->default(0)->change();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_crypto')->default(false)->change();
        });

        Schema::table('account_transactions', function (Blueprint $table) {
            $table->boolean('is_crypto')->default(false)->change();
        });

        // Update existing null values to false or 0
        \DB::table('clients')->whereNull('is_bank')->update(['is_bank' => false]);
        \DB::table('clients')->whereNull('parent_id')->update(['parent_id' => 0]);
        \DB::table('accounts')->whereNull('is_crypto')->update(['is_crypto' => false]);
        \DB::table('account_transactions')->whereNull('is_crypto')->update(['is_crypto' => false]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
