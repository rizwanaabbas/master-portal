<?php

namespace App\Models;

use App\Helpers\ChangeLogger;
use App\Helpers\Common;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Casts\CleanHtml;
use OwenIt\Auditing\Contracts\Auditable;

class ClientIndividual extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    public $timestamps = true;
    protected $table = "client_individuals";

    protected $fillable = [
        'client_id',
        'dob',
        'nationality_id',
        'country_code_id',
        'mailing_address',
        'gender',
        'mobile_number',
        'email_address',
        'passport_number',
        'passport_expiry_date',
        'agree_declaration',
        'occupation_code'
    ];

    protected $casts = [
        'dob' => CleanHtml::class,
        'nationality_id' => CleanHtml::class,
        'country_code_id' => CleanHtml::class,
        'mailing_address' => CleanHtml::class,
        'gender' => CleanHtml::class,
        'mobile_number' => CleanHtml::class,
        'email_address' => CleanHtml::class,
        'passport_number' => CleanHtml::class,
        'passport_expiry_date' => CleanHtml::class
    ];

    public static function boot()
    {
        parent::boot();

        self::updating(function ($model) {

            if ($model->client->status == "Approved") {
                // log changes for approval if user can't make the change
                if (!Common::canUserMakeChange()) {
                    ChangeLogger::log($model, "Update client details", url()->previous());
                    return false;
                }
            }
        });
    }

    public function employer()
    {
        return $this->hasOne(ClientIndividualEmployer::class, 'individual_id', 'id');
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'individual_id', 'id');
    }

    public function files()
    {
        return $this->hasMany(ClientIndividualFile::class, 'individual_id', 'id');
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function nationality()
    {
        return $this->hasOne(Country::class, 'id', 'nationality_id');
    }

    public function residence()
    {
        return $this->hasOne(Country::class, 'id', 'residence_country_id');
    }

    public function country()
    {
        return $this->hasOne(Country::class, 'id', 'country_code_id');
    }
}
