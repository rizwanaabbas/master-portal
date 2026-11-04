<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->string('name', 120)->change();
             $table->string('short', 256)->change();
        });
    }

    public function down()
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->string('name', 255)->change(); // revert to default
            $table->string('short', 10)->change(); // revert to default
        });
    }
};