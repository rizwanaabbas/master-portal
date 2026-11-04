<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrxHashToCardStatementsTable extends Migration
{
    public function up()
    {
        Schema::table('card_statements', function (Blueprint $table) {
            if (!Schema::hasColumn('card_statements', 'trx_hash')) {
                $table->string('trx_hash')->nullable()->after('transaction_nature'); // adjust 'after' field if needed
            }
        });
    }

    public function down()
    {
        Schema::table('card_statements', function (Blueprint $table) {
            if (Schema::hasColumn('card_statements', 'trx_hash')) {
                $table->dropColumn('trx_hash');
            }
        });
    }
}
