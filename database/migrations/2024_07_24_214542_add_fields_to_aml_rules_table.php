<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToAmlRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aml_rules', function (Blueprint $table) {
            // Add fields only if they do not exist
            if (!Schema::hasColumn('aml_rules', 'field')) {
                $table->string('field')->nullable();
            }

            if (!Schema::hasColumn('aml_rules', 'value')) {
                $table->text('value')->nullable();
            }

            if (!Schema::hasColumn('aml_rules', 'status')) {
                $table->text('status')->nullable();
            }

            if (!Schema::hasColumn('aml_rules', 'badge')) {
                $table->text('badge')->nullable();
            }
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
            // Drop columns if they exist
            if (Schema::hasColumn('aml_rules', 'field')) {
                $table->dropColumn('field');
            }

            if (Schema::hasColumn('aml_rules', 'value')) {
                $table->dropColumn('value');
            }

            if (Schema::hasColumn('aml_rules', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('aml_rules', 'badge')) {
                $table->dropColumn('badge');
            }
        });
    }
}

