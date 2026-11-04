<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientCompanyFile extends Model
{
    use HasFactory;
    protected $fillable = [
        'client_id',
        'file_id',
        'company_id',
        'url',
        'created_by',
    ];
    public $timestamps = true;
    protected $table = "client_company_files";
    public function director()
    {
        return $this->hasOne(ClientDirector::class, 'id', 'director_id');
    }
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }
    public function clientFileConfig()
    {
        return $this->belongsTo(ClientFileConfig::class, 'file_id');
    }
}
