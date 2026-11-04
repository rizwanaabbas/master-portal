<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transaction_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('show_on_front')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->timestamps();
        });
        DB::table('transaction_methods')->insert([
            ['name' => 'SEPA', 'show_on_front' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'SEPA_INSTANT', 'show_on_front' => 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'SWIFT', 'show_on_front' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_methods');
    }
};
