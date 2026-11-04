<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SenderDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function trRequest()
    {
        return $this->belongsTo(TRRequest::class, 'tr_request_id');
    }

    public function senderCountry()
    {
        return $this->belongsTo(Country::class, 'sender_country');
    }

    public function sendingBankCountry()
    {
        return $this->belongsTo(Country::class, 'sending_bank_country');
    }
}
