<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSanctionsReportsToClientDirectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_directors', function (Blueprint $table) {
            $table->text('sanctions_reports')->nullable()->after('client_id');
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
            $table->dropColumn('sanctions_reports');
        });
    }
}
