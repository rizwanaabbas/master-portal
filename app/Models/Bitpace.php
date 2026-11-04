<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Casts\CleanHtml;
use OwenIt\Auditing\Contracts\Auditable;
class Bitpace extends Model
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    public $timestamps = true;


}
