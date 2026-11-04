<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_method_id')->nullable()->after('crypto_system');
        });

        Schema::table('account_transaction_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_method_id')->nullable()->after('crypto_system');
        });
    }

    public function down(): void
    {
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropColumn('transaction_method_id');
        });

        Schema::table('account_transaction_requests', function (Blueprint $table) {
            $table->dropColumn('transaction_method_id');
        });
    }
};
