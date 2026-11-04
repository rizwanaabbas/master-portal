<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableClientBeneficiariesTableAddDeleteColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_beneficiaries', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0)->after('status')->nullable();
            $table->datetime('deleted_at')->after('rejected_at')->nullable();
            $table->integer('deleted_by')->after('rejected_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_beneficiaries', function (Blueprint $table) {
            $table->dropColumn('is_deleted');
            $table->datetime('deleted_at');
            $table->integer('deleted_by');
        });
    }
}
