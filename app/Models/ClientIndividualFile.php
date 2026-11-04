<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientIndividualFile extends Model
{
    use HasFactory;
    protected $fillable = [
        'client_id',
        'file_id',
        'individual_id',
        'url',
        'created_by',
    ];

    public function clientFileConfig()
    {
        return $this->belongsTo(ClientFileConfig::class, 'file_id');
    }
}
