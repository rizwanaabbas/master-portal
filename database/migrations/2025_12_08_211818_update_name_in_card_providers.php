<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class UpdateNameInCardProviders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('card_providers')->where('handle', 'vista_money')->update(['name' => 'VistaMoney']);
        DB::table('card_providers')->where('handle', 'match_move')->update(['name' => 'MatchMove']);
        DB::table('card_providers')->where('handle', 'e_money')->update(['name' => 'E-Money']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('card_providers')->where('handle', 'vista_money')->update(['name' => 'Vista Money']);
        DB::table('card_providers')->where('handle', 'match_move')->update(['name' => 'Match Move']);
        DB::table('card_providers')->where('handle', 'e_money')->update(['name' => 'E-Money']);
    }
}
