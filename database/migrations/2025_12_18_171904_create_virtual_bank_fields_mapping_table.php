<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('virtual_bank_fields_mapping', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->string('provider_name', 50)->index();
            $table->string('field_name');
            $table->string('local_value')->nullable();
            $table->timestamps();

            $table->unique(
                ['provider_name', 'field_name'],
                'vb_fields_provider_field_unique'
            );
        });

        DB::table('openpayd_fields_mapping')->orderBy('id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('virtual_bank_fields_mapping')->insert([
                    'legacy_id' => $row->id,
                    'provider_name' => 'openpayd',
                    'field_name' => $row->field_name,
                    'local_value' => $row->local_value,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_bank_fields_mapping');
    }
};
