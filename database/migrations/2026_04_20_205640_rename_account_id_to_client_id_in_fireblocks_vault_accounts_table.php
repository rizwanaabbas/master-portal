<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fireblocks_vault_accounts', function (Blueprint $table) {
            $table->renameColumn('account_id', 'client_id');
        });
    }

    public function down(): void
    {
        Schema::table('fireblocks_vault_accounts', function (Blueprint $table) {
            $table->renameColumn('client_id', 'account_id');
        });
    }
};