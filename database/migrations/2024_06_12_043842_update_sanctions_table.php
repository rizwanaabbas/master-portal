<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSanctionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sanctions', function (Blueprint $table) {
            // Change column type and make it nullable
            $table->longText('w_passport_response')->nullable()->change();

            // Add new columns
            $table->string('passport_case_id')->nullable();
            $table->string('report_url')->nullable();
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
            // Revert the column type and make it non-nullable
            $table->string('w_passport_response')->change();

            // Drop the new columns
            $table->dropColumn('passport_case_id');
            $table->dropColumn('report_url');
        });
    }
}
