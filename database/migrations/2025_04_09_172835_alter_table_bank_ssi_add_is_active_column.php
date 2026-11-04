<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableBankSsiAddIsActiveColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banks_ssi', function (Blueprint $table) {
            $table->boolean('is_active')->after('ssi_details')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banks_ssi', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
}
