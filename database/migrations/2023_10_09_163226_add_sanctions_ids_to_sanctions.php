<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSanctionsIdsToSanctions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sanctions', function (Blueprint $table) {
            $table->string('case_id')->nullable(); // Change the position if needed
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sanctions', function (Blueprint $table) {
            $table->dropColumn('case_id');
        });
    }
}
