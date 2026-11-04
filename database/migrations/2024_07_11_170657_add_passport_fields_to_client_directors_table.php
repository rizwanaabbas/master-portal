<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPassportFieldsToClientDirectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_directors', function (Blueprint $table) {
            $table->string('passport_number')->nullable()->after('email');
            $table->date('passport_expiry')->nullable()->after('passport_number');
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
            $table->dropColumn('passport_number');
            $table->dropColumn('passport_expiry');
        });
    }
}
