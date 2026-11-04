<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResidenceCountryIdToClientDirectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_directors', function (Blueprint $table) {
            // Check if the column does not exist before adding
            if (!Schema::hasColumn('client_directors', 'residence_country_id')) {
                $table->integer('residence_country_id')->nullable()->after('nationality_id');
            }
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
            $table->dropColumn('residence_country_id');
        });
    }
}

