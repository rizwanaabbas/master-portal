<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelChanges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('model_changes', function (Blueprint $table) {
            $table->id();
            $table->string("model");
            $table->string("title");
            $table->text('url');
            $table->integer('row_id');
            $table->integer("maker_id");
            $table->integer("checker_id")->nullable();
            $table->string("status");
            $table->text("reject_reason")->nullable();
            $table->timestamps();
        });

        Schema::create('model_change_details', function (Blueprint $table) {
            $table->id();
            $table->integer("change_id");
            $table->string("key");
            $table->string("old_value");
            $table->string("new_value");
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('model_changes');
    }
}
