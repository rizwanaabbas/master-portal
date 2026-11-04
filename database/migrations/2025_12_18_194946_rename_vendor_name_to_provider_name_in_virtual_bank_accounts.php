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
        if (Schema::hasColumn('virtual_bank_accounts', 'vendor_name')) {
            Schema::table('virtual_bank_accounts', function (Blueprint $table) {
                $table->renameColumn('vendor_name', 'provider_name');
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
        if (Schema::hasColumn('virtual_bank_accounts', 'provider_name')) {
            Schema::table('virtual_bank_accounts', function (Blueprint $table) {
                $table->renameColumn('provider_name', 'vendor_name');
            });
        }
    }
};

