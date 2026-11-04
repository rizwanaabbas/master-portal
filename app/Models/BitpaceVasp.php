<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BitpaceVasp extends Model
{
    use HasFactory;

    // Table name
    protected $table = 'bitpace_vasp_list';

    // Mass assignable fields
    protected $fillable = [
        'did',
        'name',
        'website',
        'logo',
        'incorporation_country',
    ];

    // Casts for convenience (optional)
    protected $casts = [
        'did' => 'string',
        'name' => 'string',
        'website' => 'string',
        'logo' => 'string',
        'incorporation_country' => 'string',
    ];
}
