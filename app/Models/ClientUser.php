<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientUser extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'id', 'client_id');
    }
}
