<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelChange extends Model
{
    use HasFactory;

    public function details()
    {
        return $this->hasMany(ModelChangeDetail::class, 'change_id', 'id');
    }

    public function maker()
    {
        return $this->hasOne(User::class, 'id', 'maker_id');
    }

    public function checker()
    {
        return $this->hasOne(User::class, 'id', 'checker_id');
    }
}
