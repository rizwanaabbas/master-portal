<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Casts\CleanHtml;
use OwenIt\Auditing\Contracts\Auditable;


class Bank extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    public $timestamps = true;

    protected $casts = [
        'name' => CleanHtml::class,
        'address' => CleanHtml::class
    ];

    public function accounts()
    {
        return $this->hasMany(Account::class, 'bank_id', 'id');
    }

    public function bankSSI()
    {
        return $this->hasMany(BanksSSI::class, 'bank_id', 'id');
    }
}
