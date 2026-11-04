<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateisCryptoInCurrencyDefaultsToFalse extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->boolean('is_crypto')->default(false)->change();
        });

        // Update existing null values to false or 0
        \DB::table('currencies')->whereNull('is_crypto')->update(['is_crypto' => false]);
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
