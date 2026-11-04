<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOwnCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('own_companies', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name', 255);
            $table->string('short_name', 50)->nullable();
            $table->string('poc_name', 255);
            $table->string('poc_email', 255)->comment('Point-of-Contact person\'s, of the company, email address.');
            $table->string('country', 50);
            $table->text('address');
            $table->string('contact_number', 50);
            $table->string('logo', 255);

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
        Schema::dropIfExists('own_companies');
    }
}
