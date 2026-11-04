<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Casts\CleanHtml;

class ClientCompanyContact extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table = "client_company_contact_person";

    protected $fillable = [
        'client_id',
        'company_id'
    ];

    protected $casts = [
        'name' => CleanHtml::class,
        'email_address' => CleanHtml::class,
        'mobile_number' => CleanHtml::class,
        'designation' => CleanHtml::class,
        'country_code_id' => CleanHtml::class,
    ];
    public function sanction_report()
    {
        return $this->hasOne(SanctionsReport::class, 'contact_id')->whereNotNull('contact_id')->latest();
    }
    public function nationality()
    {
        return $this->hasOne(Country::class, 'id', 'nationality_id');
    }

    public function residence()
    {
        return $this->hasOne(Country::class, 'id', 'residence_country_id');
    }
}
