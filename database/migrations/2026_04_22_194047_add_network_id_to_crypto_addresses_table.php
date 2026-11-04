<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crypto_addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('network_id')
                  ->nullable()
                  ->after('account_id'); // adjust position if needed

            // Optional: add index (recommended)
            $table->index('network_id', 'crypto_addresses_network_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('crypto_addresses', function (Blueprint $table) {
            $table->dropIndex('crypto_addresses_network_id_index');
            $table->dropColumn('network_id');
        });
    }
};