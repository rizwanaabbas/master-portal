<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRuleCriteriaInAmlRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aml_rules', function (Blueprint $table) {
            // Change rule_criteria type to text
            $table->text('rule_criteria')->change();
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
            // Revert rule_criteria back to its original type (string in this case)
            $table->string('rule_criteria')->change();
        });
    }
}
