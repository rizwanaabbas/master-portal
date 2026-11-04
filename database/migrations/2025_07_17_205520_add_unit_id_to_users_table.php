<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitIdToUsersTable extends Migration
{
    public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->unsignedBigInteger('unit_id')->nullable()->default(null);
        // No foreign key constraint added
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('unit_id');
    });
}

}
