<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDefaultColumnTransactionMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_methods', function (Blueprint $table) {
            // Add the 'is_default' column to the 'transaction_methods' table
            $table->boolean('is_default')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_methods', function (Blueprint $table) {
            // Drop the 'is_default' column from the 'transaction_methods' table
            $table->dropColumn('is_default');
        });
    }
}
