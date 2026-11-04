<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequiredForProvidersColumnToClientFilesConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_files_config', function (Blueprint $table) {
            $table->string('required_for_providers')->nullable()->after('identifier');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_files_config', function (Blueprint $table) {
            $table->dropColumn('required_for_providers');
        });
    }
}
