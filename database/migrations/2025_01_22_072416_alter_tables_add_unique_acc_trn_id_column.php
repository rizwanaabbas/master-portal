<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accoun_transaction_request_docs', function (Blueprint $table) {
            $table->char('acc_trn_unique_id', 36)->after('id')->nullable();
        });

        Schema::table('account_transactions', function (Blueprint $table) {
            $table->char('docs_unique_id', 36)->after('id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accoun_transaction_request_docs', function (Blueprint $table) {
            $table->dropColumn('acc_trn_unique_id');
        });

        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropColumn('docs_unique_id');
        });
    }
};
