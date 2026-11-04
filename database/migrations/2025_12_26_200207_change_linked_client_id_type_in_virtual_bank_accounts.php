<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('virtual_bank_accounts', function (Blueprint $table) {
            $table->string('linked_client_id', 100)->change();
        });
    }

    public function down(): void
    {
        Schema::table('virtual_bank_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('linked_client_id')->change();
        });
    }
};
