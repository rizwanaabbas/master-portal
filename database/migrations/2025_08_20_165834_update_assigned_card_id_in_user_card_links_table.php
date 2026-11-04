<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_card_links', function (Blueprint $table) {
            $table->integer('assigned_card_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_card_links', function (Blueprint $table) {
            // Change it back to the original type (adjust as needed)
            $table->string('assigned_card_id')->nullable(false)->change();
        });
    }
};
