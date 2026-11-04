<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTextToDropdownConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dropdown_configurations', function (Blueprint $table) {
            $table->string('text')->nullable()->after('value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dropdown_configurations', function (Blueprint $table) {
            $table->dropColumn('text');
        });
    }
}
