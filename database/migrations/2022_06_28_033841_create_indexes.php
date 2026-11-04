<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->index("account_id");
            $table->index("currency_id");
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->index("client_id");
            $table->index("company_id");
            $table->index("individual_id");
            $table->index("bank_id");
            $table->index("account_currency");
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->index("name");
        });

        Schema::table('client_users', function (Blueprint $table) {
            $table->index("client_id");
            $table->index("user_id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('indexes');
    }
}
