<?php

namespace App\Modules\Aml\Models;

use Illuminate\Database\Eloquent\Model;

class AmlData extends Model
{
    protected $table = 'aml_data';

    protected $fillable = [
        'name',
        'fields',
        'created_at',
        'updated_at'
    ];
    
    public function amlRules()
    {
        return $this->hasMany(AmlRule::class, 'data_id', 'id'); // Fix column reference
    }
    // protected $casts = [
    //     'rule_criteria' => 'array',
    // ];
}

