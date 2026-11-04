<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            // nullable -> falls back to the group flagged is_default.
            $table->unsignedBigInteger('fee_tier_group_id')->nullable()->after('id');
        });
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('fee_tier_group_id');
        });
    }
};
