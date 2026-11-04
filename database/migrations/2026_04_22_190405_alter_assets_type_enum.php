<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            ALTER TABLE assets 
            MODIFY COLUMN type ENUM('utxo','account','token','fiat') NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE assets 
            MODIFY COLUMN type ENUM('utxo','account','token') NULL
        ");
    }
};
