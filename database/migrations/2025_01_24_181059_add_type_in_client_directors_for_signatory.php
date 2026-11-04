<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeInClientDirectorsForSignatory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_directors', function (Blueprint $table) {
            // Add type column
            $table->string('type')->default('director')->after('client_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_directors_for_signatory', function (Blueprint $table) {
            //  Drop type column
            $table->dropColumn('type');
        });
    }
}
