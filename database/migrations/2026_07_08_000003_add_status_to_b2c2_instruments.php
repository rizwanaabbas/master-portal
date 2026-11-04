<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('b2c2_instruments', function (Blueprint $table) {
            // User-controlled enable/disable flag. Never overwritten by API sync.
            $table->boolean('is_active')->default(true)->after('to_currency_id');
        });
    }

    public function down()
    {
        Schema::table('b2c2_instruments', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
