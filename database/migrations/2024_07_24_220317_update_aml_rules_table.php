<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAmlRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aml_rules', function (Blueprint $table) {
            // Make rule_description nullable
            $table->string('rule_description')->nullable()->change();
            
            // Add new field data_id
            $table->unsignedBigInteger('data_id')->nullable();

            // If data_id should be a foreign key, uncomment the lines below and adjust accordingly
            // $table->foreign('data_id')->references('id')->on('data_table_name')
            //       ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aml_rules', function (Blueprint $table) {
            // Revert rule_description to not nullable
            $table->string('rule_description')->nullable(false)->change();
            
            // Drop the data_id field
            $table->dropColumn('data_id');

            // If data_id was a foreign key, uncomment the line below to drop the foreign key
            // $table->dropForeign(['data_id']);
        });
    }
}
