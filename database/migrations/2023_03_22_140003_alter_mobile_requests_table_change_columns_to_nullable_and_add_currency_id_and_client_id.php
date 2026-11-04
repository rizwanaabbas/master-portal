<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterMobileRequestsTableChangeColumnsToNullableAndAddCurrencyIdAndClientId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mobile_requests', function (Blueprint $table) {
            $table->integer('client_id')->after('user_id');
            $table->integer('currency_id')->after('client_id')->nullable();
            $table->integer('approved_by')->nullable()->change();
            $table->dateTime('approved_at')->nullable()->change();
            $table->integer('rejected_by')->nullable()->change();
            $table->dateTime('rejected_at')->nullable()->change();
            $table->text('reject_reason')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mobile_requests', function (Blueprint $table) {
            //
        });
    }
}
