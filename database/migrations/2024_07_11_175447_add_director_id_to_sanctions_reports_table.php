<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDirectorIdToSanctionsReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sanctions_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('director_id')->nullable()->after('client_id');
            $table->unsignedBigInteger('contact_id')->nullable()->after('director_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sanctions_reports', function (Blueprint $table) {
            $table->dropColumn('director_id');
            $table->dropColumn('contact_id');
        });
    }
}

