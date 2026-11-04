<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    public function enabler()
    {
        return $this->hasOne(User::class, 'id', 'enabled_disabled_by');
    }
    public function configuration()
    {
         return $this->hasOne(ModuleConfiguration::class, 'module_id', 'id');
    }
}
