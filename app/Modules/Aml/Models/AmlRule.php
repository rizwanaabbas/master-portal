<?php

namespace App\Modules\Aml\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmlRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_name',
        'field',
        'rule_criteria',
        'value',
        'country_id',
        'status',
        'badge',
        'rule_description',
        'data_id',
    ];
    public function amlData()
    {
        return $this->hasOne(AmlData::class, 'id', 'data_id');
    }
    // protected $casts = [
    //     'country_id' => 'array', // Cast to array to handle JSON encoding/decoding
    // ];
}
