<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToClientDirectors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_directors', function (Blueprint $table) {
            // add user_id column
            $table->unsignedBigInteger('user_id')->nullable()->after('client_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_directors', function (Blueprint $table) {
            // drop user_id column
            $table->dropColumn('user_id');
        });
    }
}
