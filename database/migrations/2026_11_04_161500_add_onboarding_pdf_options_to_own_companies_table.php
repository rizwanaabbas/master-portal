<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnboardingPdfOptionsToOwnCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('own_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('own_companies', 'onboarding_pdf_options')) {
                $table->json('onboarding_pdf_options')->nullable()->after('theme');
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
        Schema::table('own_companies', function (Blueprint $table) {
            if (Schema::hasColumn('own_companies', 'onboarding_pdf_options')) {
                $table->dropColumn('onboarding_pdf_options');
            }
        });
    }
}
