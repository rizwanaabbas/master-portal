<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_units_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('name');
            $table->timestamps();
        });

       
    }

    public function down(): void
    {
        

        Schema::dropIfExists('units');
    }
};
