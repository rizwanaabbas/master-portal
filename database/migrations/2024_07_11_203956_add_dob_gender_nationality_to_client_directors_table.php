<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDobGenderNationalityToClientDirectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_directors', function (Blueprint $table) {
            $table->date('dob')->nullable()->after('passport_expiry');
            $table->string('gender')->nullable()->after('dob');
            //$table->string('gender', 6)->change();
            $table->unsignedBigInteger('nationality_id')->nullable();
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
            $table->dropColumn(['dob', 'gender', 'nationality_id']);
        });
    }
}

