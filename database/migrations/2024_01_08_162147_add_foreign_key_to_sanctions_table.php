<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeyToSanctionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sanctions', function (Blueprint $table) {
            $table->foreignId('report_id')->nullable()->constrained('sanctions_reports');
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
            $table->dropForeign(['report_id']);
        });
    }
}
