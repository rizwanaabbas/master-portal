<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDirectorIdToClientCompanyFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_company_files', function (Blueprint $table) {
            $table->unsignedBigInteger('director_id')->nullable()->after('client_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_company_files', function (Blueprint $table) {
            $table->dropColumn('director_id');
        });
    }
}
