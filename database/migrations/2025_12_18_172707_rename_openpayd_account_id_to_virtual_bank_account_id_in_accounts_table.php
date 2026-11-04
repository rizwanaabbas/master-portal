<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'openpayd_account_id')) {
                $table->renameColumn(
                    'openpayd_account_id',
                    'virtual_bank_account_id'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'virtual_bank_account_id')) {
                $table->renameColumn(
                    'virtual_bank_account_id',
                    'openpayd_account_id'
                );
            }
        });
    }
};

