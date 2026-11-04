<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddThemeCardColorsChartColorsToOwnCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('own_companies', function (Blueprint $table) {
            $table->string('theme')->nullable()->after('max_file_size');
            $table->json('card_colors')->nullable()->after('theme');
            $table->string('chart_color')->nullable()->after('card_colors');
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
            $table->dropColumn([
                'theme',
                'card_colors',
                'chart_color'
            ]);
        });
    }
}
