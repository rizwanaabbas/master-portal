<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStreet2AddressColumnToClientIndividual extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_individuals', function (Blueprint $table) {
            $table->string('street_2_address')->nullable()->after('street_address');
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
            $table->dropColumn('street_2_address');
        });
    }
}
