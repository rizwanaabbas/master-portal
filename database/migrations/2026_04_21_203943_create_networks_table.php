<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('networks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // BTC, ETH, TRX, BSC
            $table->string('name');           // Bitcoin, Ethereum, Tron
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('networks');
    }
};