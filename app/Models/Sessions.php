<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sessions extends Model
{
    use HasFactory;
    
    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

}
