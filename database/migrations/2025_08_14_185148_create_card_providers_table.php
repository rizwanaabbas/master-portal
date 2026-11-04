<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('card_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('handle')->unique();
            $table->timestamps();
        });

        // Insert default records
        DB::table('card_providers')->insert([
            ['name' => 'VistaMoney', 'handle' => 'vista_money', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'MatchMove', 'handle' => 'match_move', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'E-Money', 'handle' => 'e_money', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_providers');
    }
};
