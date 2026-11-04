<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Create NAICS Codes Table
 * US industry code as defined by the North American Industry Classification System (NAICS) system. 
 * See https://reference.fuse.me/naics.html for supported list of codes.
*/

class CreateNaicsCodesTable extends Migration
{   
    public function up()
    {
       if (!Schema::hasTable('naics_codes')) {
            Schema::create('naics_codes', function (Blueprint $table) {
                $table->id();
                $table->string('naics_code')->unique();
                $table->string('naics_title');
                $table->timestamps();
            });
        }

        $response = Http::timeout(15)->get(
            'https://reference.fuse.me/naics_codes.json'
        );

        if (!$response->successful()) {
            return;
        }

        $naicsCodes = $response->json();

        if (!is_array($naicsCodes)) {
            return;
        }

        $now = now();
        $insertData = [];

        foreach ($naicsCodes as $item) {
            if (
                empty($item['naics_code']) ||
                empty($item['naics_title'])
            ) {
                continue;
            }

            $insertData[] = [
                'naics_code'  => $item['naics_code'], // no normalization
                'naics_title' => $item['naics_title'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        // ✅ Let DB handle duplicates safely
        foreach (array_chunk($insertData, 500) as $chunk) {
            DB::table('naics_codes')->insertOrIgnore($chunk);
        }
    }

    public function down()
    {
        Schema::dropIfExists('naics_codes');
    }
}
