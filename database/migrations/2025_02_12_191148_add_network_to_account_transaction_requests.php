<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('account_transaction_requests', function (Blueprint $table) {
            $table->string('crypto_network')->nullable()->default(null)->after('to_wallet_address'); // Replace 'column_name' with the actual column after which you want to add 'network'
        });
    }

    public function down(): void
    {
        Schema::table('account_transaction_requests', function (Blueprint $table) {
            $table->dropColumn('crypto_network');
        });
    }
};
