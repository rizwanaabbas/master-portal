<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PerformanceReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // index accounts transactions
        Schema::table('account_transactions', function(Blueprint $table)
        {
            $table->index('transaction_date');
            $table->index('transaction_type');
            $table->index('transaction_nature');
            $table->index('status');
        });

        // index accounts
        Schema::table('accounts', function(Blueprint $table)
        {
            $table->index('is_active');
            $table->index('type');
        });

        // index clients
        Schema::table('clients', function(Blueprint $table)
        {
            $table->index('status');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
