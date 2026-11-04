<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Casts\CleanHtml;

class ClientIndividualEmployer extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table = "client_individuals_employer_details";

    protected $fillable = [
        'client_id',
        'individual_id',
    ];

    protected $casts = [
        'occupation_business' => CleanHtml::class,
        'employer_business_name' => CleanHtml::class,
        'employer_address' => CleanHtml::class,
        'job_title' => CleanHtml::class,
        'applicant_annual_income_usd' => CleanHtml::class
    ];
}
