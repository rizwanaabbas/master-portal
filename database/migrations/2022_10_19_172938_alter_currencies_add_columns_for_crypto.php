<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCurrenciesAddColumnsForCrypto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->double('min_amount')->after('is_active')->nullable();
            $table->double('max_amount')->after('min_amount')->nullable();
            $table->boolean('is_crypto')->after('max_amount')->nullable();
            $table->text('logo')->after('is_crypto')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
