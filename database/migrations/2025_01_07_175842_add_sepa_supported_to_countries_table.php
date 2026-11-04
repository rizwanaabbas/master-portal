<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddSepaSupportedToCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('countries', function (Blueprint $table) {
            
            // Add sepa_supported column to countries table
            Schema::table('countries', function (Blueprint $table) {
                $table->boolean('sepa_supported')->default(false)->after('iso');
            });
    
            // List of ISO codes for SEPA-supported countries
            $sepaCountries = [
                'FI', 'AT', 'PT', 'BE', 'BG', 'ES', 'HR', 'CY', 'CZ', 'DK', 'EE', 
                'FR', 'DE', 'GI', 'GR', 'HU', 'IS', 'IE', 'IT', 'LV', 'LI', 'LT', 
                'LU', 'MT', 'MC', 'NL', 'NO', 'PL', 'RO', 'SK', 'SI', 'SE', 'CH', 
                'GB', 'GF', 'GP', 'YT', 'MQ', 'RE', 'BL', 'MF', 'PM', 'SM', 'IM', 
                'GG', 'JE', 'AZ', 'FI'
            ];
    
            // Update sepa_supported column to true for specified ISO codes
            DB::table('countries')
                ->whereIn('iso', $sepaCountries)
                ->update(['sepa_supported' => true]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('countries', function (Blueprint $table) {
            // Drop sepa_supported column from countries table
            $table->dropColumn('sepa_supported');
        });
    }
}
