<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterClientsAddNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        return true;
        Schema::table('clients', function (Blueprint $table) {

            $table->boolean('send_welcome_email')->after('status')->nullable();
            $table->boolean('send_ssi')->after('send_welcome_email')->nullable();
            $table->boolean('create_user')->after('send_ssi')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
