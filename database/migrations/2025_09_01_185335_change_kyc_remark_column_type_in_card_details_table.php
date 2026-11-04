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
        Schema::table('card_details', function (Blueprint $table) {
            $table->text('kyc_remark')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_details', function (Blueprint $table) {
            $table->string('kyc_remark', 255)->change(); // assuming it was string before
        });
    }
};
