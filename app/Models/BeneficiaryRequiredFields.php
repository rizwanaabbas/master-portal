<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeneficiaryRequiredFields extends Model
{
    use HasFactory;
    protected $table = "beneficiary_required_fields";
     protected $fillable = [
        'country_id',
        'currency_id',
        'beneficiary_type',
        'required_fields',
    ];

    
}
