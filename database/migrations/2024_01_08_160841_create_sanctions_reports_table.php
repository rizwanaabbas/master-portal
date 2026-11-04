<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSanctionsReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sanctions_reports', function (Blueprint $table) {
            $table->id();
            $table->integer("client_id");
            $table->string('case_system_id')->nullable();
            $table->string('case_report_id')->nullable();
            $table->string('status')->nullable();
            $table->string('url')->nullable();
            $table->boolean('requested')->default(0);
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
        Schema::dropIfExists('sanctions_reports');
    }
}
