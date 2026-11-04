<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Create SOC Codes Table
 * US occupation code as defined by the Standard Occupational Classification (SOC) system.
 * See https://reference.fuse.me/soc for supported list of codes.
 */

class CreateSocCodesTable extends Migration
{
    public function up()
    {
        Schema::create('soc_codes', function (Blueprint $table) {
            $table->id();
            $table->string('soc_code')->unique();
            $table->string('job_title');
            $table->string('eeo_title');
            $table->timestamps();
        });

        $response = Http::get('https://reference.fuse.me/soc_codes.json');

        if ($response->successful()) {
            $socCodes = $response->json();

            $now = now();
            $unique = [];

            foreach ($socCodes as $item) {
                $socCode = str_replace('-', '', $item['soc_code']);

                // Only keep first occurrence
                if (!isset($unique[$socCode])) {
                    $unique[$socCode] = [
                        'soc_code'   => $socCode,
                        'job_title' => $item['job_title'],
                        'eeo_title' => $item['eeo_title'] ?? null,
                        'created_at'=> $now,
                        'updated_at'=> $now,
                    ];
                }
            }

            DB::table('soc_codes')->insert(array_values($unique));
        }
    }

    public function down()
    {
        Schema::dropIfExists('soc_codes');
    }
}
