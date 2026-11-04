<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Currency;

class CardStatement extends Model
{
    use HasFactory;
    protected $guarded = [];
     public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'processing_currency');
    }
}
