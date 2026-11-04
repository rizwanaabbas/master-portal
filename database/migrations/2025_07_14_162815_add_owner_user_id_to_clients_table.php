<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOwnerUserIdToClientsTable extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_user_id')->nullable()->after('is_kyc_requested');

            // Optional: add a foreign key constraint
            // $table->foreign('owner_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // If you added a foreign key constraint, drop it first
            // $table->dropForeign(['owner_user_id']);

            $table->dropColumn('owner_user_id');
        });
    }
}
