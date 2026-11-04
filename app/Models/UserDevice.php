<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    use HasFactory;

    protected $table = "user_devices";

    protected $hidden = [
        'passcode'
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
