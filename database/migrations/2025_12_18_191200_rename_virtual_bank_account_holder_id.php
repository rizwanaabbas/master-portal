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
        // virtual_bank_accounts table
        if (Schema::hasColumn('virtual_bank_accounts', 'virtual_bank_account_holder_id')) {
            Schema::table('virtual_bank_accounts', function (Blueprint $table) {
                $table->renameColumn('virtual_bank_account_holder_id', 'account_holder_id');
            });
        }

        // virtual_bank_beneficiaries table
        if (Schema::hasColumn('virtual_bank_beneficiaries', 'virtual_bank_account_holder_id')) {
            Schema::table('virtual_bank_beneficiaries', function (Blueprint $table) {
                $table->renameColumn('virtual_bank_account_holder_id', 'account_holder_id');
            });
        }

        // virtual_bank_linked_clients table
        if (Schema::hasColumn('virtual_bank_linked_clients', 'virtual_bank_account_holder_id')) {
            Schema::table('virtual_bank_linked_clients', function (Blueprint $table) {
                $table->renameColumn('virtual_bank_account_holder_id', 'account_holder_id');
            });
        }

        // virtual_bank_transactions table
        if (Schema::hasColumn('virtual_bank_transactions', 'virtual_bank_account_holder_id')) {
            Schema::table('virtual_bank_transactions', function (Blueprint $table) {
                $table->renameColumn('virtual_bank_account_holder_id', 'account_holder_id');
            });
        }

        // virtual_bank_fields_mapping table
        if (Schema::hasColumn('virtual_bank_fields_mapping', 'virtual_bank_account_holder_id')) {
            Schema::table('virtual_bank_fields_mapping', function (Blueprint $table) {
                $table->renameColumn('virtual_bank_account_holder_id', 'account_holder_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert column names
        if (Schema::hasColumn('virtual_bank_accounts', 'account_holder_id')) {
            Schema::table('virtual_bank_accounts', function (Blueprint $table) {
                $table->renameColumn('account_holder_id', 'virtual_bank_account_holder_id');
            });
        }

        if (Schema::hasColumn('virtual_bank_beneficiaries', 'account_holder_id')) {
            Schema::table('virtual_bank_beneficiaries', function (Blueprint $table) {
                $table->renameColumn('account_holder_id', 'virtual_bank_account_holder_id');
            });
        }

        if (Schema::hasColumn('virtual_bank_linked_clients', 'account_holder_id')) {
            Schema::table('virtual_bank_linked_clients', function (Blueprint $table) {
                $table->renameColumn('account_holder_id', 'virtual_bank_account_holder_id');
            });
        }

        if (Schema::hasColumn('virtual_bank_transactions', 'account_holder_id')) {
            Schema::table('virtual_bank_transactions', function (Blueprint $table) {
                $table->renameColumn('account_holder_id', 'virtual_bank_account_holder_id');
            });
        }

        if (Schema::hasColumn('virtual_bank_fields_mapping', 'account_holder_id')) {
            Schema::table('virtual_bank_fields_mapping', function (Blueprint $table) {
                $table->renameColumn('account_holder_id', 'virtual_bank_account_holder_id');
            });
        }
    }
};

