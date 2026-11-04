<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('openpayd_bank_account', function (Blueprint $table) {
            $table->unsignedBigInteger('currency_id')
                  ->nullable()
                  ->default(null)
                  ->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('openpayd_bank_account', function (Blueprint $table) {
            $table->dropColumn('currency_id');
        });
    }
};
