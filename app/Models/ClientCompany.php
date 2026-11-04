<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Casts\CleanHtml;
use OwenIt\Auditing\Contracts\Auditable;

class ClientCompany extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    public $timestamps = true;
    protected $table = "client_companies";

    protected $fillable = [
        'client_id'
    ];

    protected $casts = [
        'name' => CleanHtml::class,
        'registration_date' => CleanHtml::class,
        'company_place' => CleanHtml::class,
        'company_type' => CleanHtml::class,
        'business_reg_number' => CleanHtml::class,
        'business_reg_address' => CleanHtml::class,
        'nature_of_business' => CleanHtml::class,
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function contact()
    {
        return $this->hasOne(ClientCompanyContact::class, 'company_id', 'id');
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'company_id', 'id');
    }

    public function files()
    {
        return $this->hasMany(ClientCompanyFile::class, 'company_id', 'id');
    }

    public function type()
    {
        return $this->hasOne(CompanyType::class, 'id', 'company_type');
    }
    
    public function companyCountry()
    {
        return $this->hasOne(Country::class, 'id', 'company_country');
    }
}
