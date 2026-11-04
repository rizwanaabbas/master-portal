<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientFileConfig extends Model
{
    use HasFactory;
    protected $table = "client_files_config";

    public function scopeIndividual($query)
    {
        return $query->where("type", "individual")->where("is_active", true);
    }

    public function scopeCompany($query)
    {
        return $query->where("type", "company")->where("is_active", true);
    }

    public function scopeBank($query)
    {
        return $query->where("type", "bank")->where("is_active", true);
    }
}
