<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStampToOwnCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('own_companies', function (Blueprint $table) {
            $table->string('stamp')->nullable()->after('favicon');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('own_companies', function (Blueprint $table) {
            $table->dropColumn('stamp');
        });
    }
}
