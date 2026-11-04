<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstantCardRequest extends Model
{
    use HasFactory;

    public function creator()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
