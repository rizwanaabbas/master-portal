<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardDetail extends Model
{
    use HasFactory;
    public function mobileRequest()
    {
        return $this->belongsTo(MobileRequest::class, 'mobile_request_id');
    }

}
