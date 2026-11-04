<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToOpenpaydLinkedClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('openpayd_linked_clients', function (Blueprint $table) {
            $table->string('linked_client_id')->nullable()->after('response_data');
            $table->string('client_id')->nullable()->after('linked_client_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('openpayd_linked_clients', function (Blueprint $table) {
            $table->dropColumn(['linked_client_id', 'client_id']);
        });
    }
}
