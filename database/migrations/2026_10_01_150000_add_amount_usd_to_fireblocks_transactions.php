<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmountUsdToFireblocksTransactions extends Migration
{
    public function up()
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('fireblocks_transactions', 'amount_usd')) {
                $table->decimal('amount_usd', 36, 8)->nullable()->after('amount');
            }
        });
    }

    public function down()
    {
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('fireblocks_transactions', 'amount_usd')) {
                $table->dropColumn('amount_usd');
            }
        });
    }
}
