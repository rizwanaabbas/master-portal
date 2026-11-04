<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Create the table
        Schema::create('bitpace_vasp_list', function (Blueprint $table) {
            $table->id();
            $table->string('did', 255)->unique();
            $table->string('name', 255);
            $table->text('website')->nullable();
            $table->text('logo')->nullable();
            $table->string('incorporation_country', 255)->nullable();
            $table->timestamps();
        });

        // 2. Load JSON file
        $jsonPath = storage_path('vasp/vasp_list.json');
        if (file_exists($jsonPath)) {
            $json = file_get_contents($jsonPath);
            $data = collect(json_decode($json, true))
                ->unique('did')       // Remove duplicates by DID
                ->values()
                ->toArray();

            $rows = [];
            foreach ($data as $item) {
                // Skip invalid entries
                if (empty($item['did']) || empty($item['name'])) continue;

                $rows[] = [
                    'did' => $item['did'],
                    'name' => $item['name'],
                    'website' => $item['website'] ?? null,
                    'logo' => $item['logo'] ?? null,
                    'incorporation_country' => $item['incorporationCountry'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert in chunks of 500
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('bitpace_vasp_list')->insert($chunk);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('bitpace_vasp_list');
    }
};