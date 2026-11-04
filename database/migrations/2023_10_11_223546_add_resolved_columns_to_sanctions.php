<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResolvedColumnsToSanctions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sanctions', function (Blueprint $table) {
            $table->boolean('resolved')->nullable();
            $table->string('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('blocked')->nullable();
            $table->string('blocked_by')->nullable();
            $table->timestamp('blocked_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sanctions', function (Blueprint $table) {
            $table->dropColumn('resolved');
            $table->dropColumn('resolved_by');
            $table->dropColumn('resolved_at');
            $table->dropColumn('blocked');
            $table->dropColumn('blocked_by');
            $table->dropColumn('blocked_at');
        });
    }
}
