<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFileNameToClientCompanyFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_company_files', function (Blueprint $table) {
            // add file_name column
            $table->string('file_name')->after('file_id')->nullable();
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
            // drop file_name column
            $table->dropColumn('file_name');
        });
    }
}
