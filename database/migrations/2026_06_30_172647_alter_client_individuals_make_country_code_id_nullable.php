<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterClientIndividualsMakeCountryCodeIdNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_individuals', function (Blueprint $table) {
            $table->integer('country_code_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_individuals', function (Blueprint $table) {
            $table->integer('country_code_id')->nullable(false)->change();
        });
    }
}
